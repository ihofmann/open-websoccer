<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

// Short-circuit the failure delay so the failed-login test does not hang.
if (!defined('SLEEP_SECONDS_ON_FAILURE')) {
	define('SLEEP_SECONDS_ON_FAILURE', 0);
}

if (!class_exists('FakeFormLoginMethod')) {
	/**
	 * Fake IUserLoginMethod whose authenticate() returns a configurable user ID.
	 */
	class FakeFormLoginMethod implements \IUserLoginMethod {
		public static $userId = 5;
		public function __construct(\WebSoccer $websoccer, \DbConnection $db) {}
		public function authenticateWithEmail($email, $password) {
			return self::$userId;
		}
		public function authenticateWithUsername($nick, $password) {
			return self::$userId;
		}
	}
}

/**
 * Unit tests for FormLoginController.
 */
final class FormLoginControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'login_method' => 'FakeFormLoginMethod',
			'login_type' => 'username',
			'supported_languages' => 'en,de',
		];
	}

	/**
	 * Builds a WebSoccer mock returning a guest User (id null) via getUser().
	 */
	private function makeWs(array $config, \User $user): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getUser')->willReturn($user);
		$ws->method('getNowAsTimestamp')->willReturn(1000000);
		$ws->method('getRequestParameter')->willReturn(null);
		return $ws;
	}

	/**
	 * Builds a DbConnection mock whose querySelect returns the supplied user row
	 * when querying the _user table (as SessionBasedUserAuthentication does).
	 */
	private function makeDbWithUser(array $userRow): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($userRow) {
			if (strpos($fromTable, '_user') !== false) {
				return $this->dbResult([$userRow]);
			}
			return $this->dbResult([]);
		});
		$db->method('queryUpdate');
		return $db;
	}

	public function testThrowsWhenLoginMethodClassDoesNotExist(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->makeWs(
			array_merge($this->config(), ['login_method' => 'NonExistentLoginMethodXyz']),
			$this->makeUser([])
		);
		$db = $this->createMock(\DbConnection::class);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Login method class does not exist: NonExistentLoginMethodXyz');

		$controller = new FormLoginController($i18n, $ws, $db);
		$controller->executeAction(['loginstr' => 'manager', 'loginpassword' => 'secret']);
	}

	public function testThrowsOnFailedAuthentication(): void {
		\FakeFormLoginMethod::$userId = FALSE;

		$i18n = $this->mockI18n(['formlogin_invalid_data' => 'invalid credentials']);
		$ws = $this->makeWs($this->config(), $this->makeUser([]));
		$db = $this->createMock(\DbConnection::class);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid credentials');

		$controller = new FormLoginController($i18n, $ws, $db);
		$controller->executeAction(['loginstr' => 'manager', 'loginpassword' => 'wrong']);
	}

	public function testSuccessfulLoginReturnsOffice(): void {
		\FakeFormLoginMethod::$userId = 5;

		$i18n = $this->mockI18n([]);
		$user = $this->makeUser([]); // guest; gets populated by the session auth.
		$ws = $this->makeWs($this->config(), $user);

		$userRow = ['id' => 5, 'nick' => 'manager', 'email' => 'manager@example.com',
			'lang' => 'en', 'premium_balance' => 0, 'picture' => ''];
		$db = $this->makeDbWithUser($userRow);

		// SessionBasedUserAuthentication uses the DbConnection and I18n singletons.
		\DbConnection::setInstanceForTesting($db);
		\I18n::setInstanceForTesting($i18n);

		$controller = new FormLoginController($i18n, $ws, $db);
		$this->assertSame('office', $controller->executeAction([
			'loginstr' => 'manager', 'loginpassword' => 'secret',
		]));

		// The session auth populated the user object.
		$this->assertSame(5, $user->id);
		$this->assertSame('manager', $user->username);
	}

	public function testSuccessfulLoginWithoutUsernameReturnsEnterUsername(): void {
		\FakeFormLoginMethod::$userId = 5;

		$i18n = $this->mockI18n([]);
		$user = $this->makeUser([]);
		$ws = $this->makeWs($this->config(), $user);

		$userRow = ['id' => 5, 'nick' => '', 'email' => 'manager@example.com',
			'lang' => 'en', 'premium_balance' => 0, 'picture' => ''];
		$db = $this->makeDbWithUser($userRow);

		\DbConnection::setInstanceForTesting($db);
		\I18n::setInstanceForTesting($i18n);

		$controller = new FormLoginController($i18n, $ws, $db);
		$this->assertSame('enter-username', $controller->executeAction([
			'loginstr' => 'manager', 'loginpassword' => 'secret',
		]));
	}

	public function testRememberMeGeneratesSaltWhenDatabaseSaltIsNull(): void {
		\FakeFormLoginMethod::$userId = 5;

		$i18n = $this->mockI18n([]);
		$user = $this->makeUser([]);
		$ws = $this->makeWs($this->config(), $user);

		$userRow = ['id' => 5, 'nick' => 'manager', 'email' => 'manager@example.com',
			'lang' => 'en', 'premium_balance' => 0, 'picture' => '',
			'passwort_salt' => null];
		$db = $this->makeDbWithUser($userRow);

		\DbConnection::setInstanceForTesting($db);
		\I18n::setInstanceForTesting($i18n);

		$controller = new FormLoginController($i18n, $ws, $db);
		$this->assertSame('office', $controller->executeAction([
			'loginstr' => 'manager', 'loginpassword' => 'secret', 'rememberme' => 1,
		]));
	}
}
