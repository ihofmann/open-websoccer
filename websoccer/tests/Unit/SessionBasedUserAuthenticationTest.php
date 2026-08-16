<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

// Force-load the class files so their top-level define() calls for
// SESSION_PARAM_USERID and COOKIE_PREFIX are available.
class_exists('SessionBasedUserAuthentication');
class_exists('CookieHelper');

/**
 * Unit tests for SessionBasedUserAuthentication.
 */
final class SessionBasedUserAuthenticationTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		// logoutUser() calls session_destroy(); restart the session so
		// subsequent tests have an active session.
		if (session_status() !== PHP_SESSION_ACTIVE) {
			@session_start();
		}
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'supported_languages' => 'en,de',
			'context_root' => '',
			'gravatar_enable' => 0,
		]);
	}

	/**
	 * Fresh DbConnection mock with a callback controlling querySelect results.
	 */
	private function mockDbWithCallback(callable $selectCallback): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback($selectCallback);
		$db->method('queryUpdate');
		return $db;
	}

	public function testVerifyGuestWithNoSessionAndNoCookieReturnsEarly(): void {
		$db = $this->mockDbWithCallback(function () {
			return new MockDbResult([]);
		});
		\DbConnection::setInstanceForTesting($db);

		$user = new \User();
		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->verifyAndUpdateCurrentUser($user);

		// Guest should remain guest.
		$this->assertNull($user->id);
	}

	public function testVerifyLoggedInUserSetsUserProperties(): void {
		$userRow = [
			'id' => 5,
			'nick' => 'manager1',
			'email' => 'user@test.local',
			'lang' => 'de',
			'premium_balance' => 100,
			'picture' => '',
		];
		$db = $this->mockDbWithCallback(function () use ($userRow) {
			return new MockDbResult([$userRow]);
		});
		\DbConnection::setInstanceForTesting($db);
		\I18n::setInstanceForTesting($this->mockI18n());

		$_SESSION[SESSION_PARAM_USERID] = 5;
		$user = new \User();
		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->verifyAndUpdateCurrentUser($user);

		$this->assertSame(5, $user->id);
		$this->assertSame('manager1', $user->username);
		$this->assertSame('user@test.local', $user->email);
		$this->assertSame(100, $user->premiumBalance);
		$this->assertSame(5, $_SESSION[SESSION_PARAM_USERID]);
	}

	public function testVerifyDisabledUserCallsLogout(): void {
		$db = $this->mockDbWithCallback(function () {
			return new MockDbResult([]);
		});
		\DbConnection::setInstanceForTesting($db);

		$_SESSION[SESSION_PARAM_USERID] = 5;
		$user = $this->makeUser(['id' => 5, 'username' => 'manager1']);
		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->verifyAndUpdateCurrentUser($user);

		// logoutUser should have cleared the user id.
		$this->assertNull($user->id);
		$this->assertNull($user->username);
	}

	public function testRememberMeWithValidTokenLogsIn(): void {
		$salt = 'mysalt';
		$userId = 7;
		// Compute the expected token (useragent is 'n.a.' since session is fresh).
		$token = md5($salt . 'n.a.' . $userId);

		$userRow = [
			'id' => $userId,
			'passwort_salt' => $salt,
			'nick' => 'remembered',
			'email' => 'rem@test.local',
			'lang' => 'en',
			'premium_balance' => 50,
			'picture' => '',
		];
		$db = $this->mockDbWithCallback(function () use ($userRow) {
			return new MockDbResult([$userRow]);
		});
		\DbConnection::setInstanceForTesting($db);
		\I18n::setInstanceForTesting($this->mockI18n());

		$_COOKIE[COOKIE_PREFIX . 'user'] = $token;
		$user = new \User();
		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->verifyAndUpdateCurrentUser($user);

		$this->assertSame($userId, $user->id);
		$this->assertSame('remembered', $user->username);
		$this->assertSame($userId, $_SESSION[SESSION_PARAM_USERID]);
	}

	public function testRememberMeWithInvalidTokenDestroysCookie(): void {
		$userRow = [
			'id' => 7,
			'passwort_salt' => 'mysalt',
			'nick' => 'remembered',
			'email' => 'rem@test.local',
			'lang' => 'en',
		];
		$updateCalled = false;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult([$userRow]));
		$db->method('queryUpdate')->willReturnCallback(function () use (&$updateCalled) {
			$updateCalled = true;
		});
		\DbConnection::setInstanceForTesting($db);

		$_COOKIE[COOKIE_PREFIX . 'user'] = 'invalid_token';
		$user = new \User();
		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->verifyAndUpdateCurrentUser($user);

		// User should not be logged in.
		$this->assertNull($user->id);
		// Token should have been cleared via queryUpdate.
		$this->assertTrue($updateCalled);
	}

	public function testRememberMeWithNoUserFoundDestroysCookie(): void {
		$db = $this->mockDbWithCallback(function () {
			return new MockDbResult([]);
		});
		\DbConnection::setInstanceForTesting($db);

		$_COOKIE[COOKIE_PREFIX . 'user'] = 'some_token';
		$user = new \User();
		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->verifyAndUpdateCurrentUser($user);

		$this->assertNull($user->id);
	}

	public function testLogoutUserClearsPropertiesForLoggedInUser(): void {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager', 'email' => 'user@test.local']);
		$_SESSION[SESSION_PARAM_USERID] = 1;
		$_SESSION['other_data'] = 'value';

		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->logoutUser($user);

		$this->assertNull($user->id);
		$this->assertNull($user->username);
		$this->assertNull($user->email);
	}

	public function testLogoutUserIsNoOpForGuest(): void {
		$user = new \User();
		$_SESSION[SESSION_PARAM_USERID] = 1;

		$auth = new SessionBasedUserAuthentication($this->ws);
		$auth->logoutUser($user);

		// Guest user: nothing should change.
		$this->assertNull($user->id);
		// Session should NOT be destroyed for a guest.
		$this->assertArrayHasKey(SESSION_PARAM_USERID, $_SESSION);
	}
}
