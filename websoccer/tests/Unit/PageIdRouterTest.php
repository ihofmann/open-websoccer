<?php
use OpenWebSoccer\Tests\TestCaseBase;

// Force the class file to load so its top-level define() calls for
// DEFAULT_PAGE_ID, LOGIN_PAGE_ID, ENTERUSERNAME_PAGE_ID are available.
class_exists('PageIdRouter');

/**
 * Unit tests for PageIdRouter.
 */
final class PageIdRouterTest extends TestCaseBase {
	/**
	 * Creates a WebSoccer mock configured with a specific user, config map,
	 * and optional request 'id' parameter.  Built from scratch (not via
	 * mockWebsoccer()) to avoid the getRequestParameter stub shadowing issue.
	 */
	private function mockWebsoccerWithUser(\User $user, array $config = [], ?string $requestId = null): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getUser')->willReturn($user);
		$ws->method('getRequestParameter')->willReturnCallback(function ($name) use ($requestId) {
			if ($name === 'id') {
				return $requestId;
			}
			return null;
		});
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('addFrontMessage')->willReturnCallback(function () {});
		return $ws;
	}

	public function testGetTargetPageIdReturnsDefaultWhenPageIdIsNull(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => 'bob']), [
			'password_protected' => '0',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame(DEFAULT_PAGE_ID, PageIdRouter::getTargetPageId($ws, $i18n, null));
	}

	public function testGetTargetPageIdReturnsRequestedPageWhenNotProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => 'bob']), [
			'password_protected' => '0',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame('teams', PageIdRouter::getTargetPageId($ws, $i18n, 'teams'));
	}

	public function testGetTargetPageIdRedirectsGuestToLoginWhenProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => null, 'username' => '']), [
			'password_protected' => '1',
			'password_protected_startpage' => '1',
		]);
		$i18n = $this->mockI18n([
			'requireslogin_box_title' => 'Login Required',
			'requireslogin_box_message' => 'Please log in.',
		]);
		$this->assertSame(LOGIN_PAGE_ID, PageIdRouter::getTargetPageId($ws, $i18n, 'teams'));
	}

	public function testGetTargetPageIdAllowsGuestToAccessLoginPageWhenProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => null, 'username' => '']), [
			'password_protected' => '1',
			'password_protected_startpage' => '1',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame(LOGIN_PAGE_ID, PageIdRouter::getTargetPageId($ws, $i18n, LOGIN_PAGE_ID));
	}

	public function testGetTargetPageIdAllowsGuestToAccessRegisterPageWhenProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => null, 'username' => '']), [
			'password_protected' => '1',
			'password_protected_startpage' => '1',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame('register', PageIdRouter::getTargetPageId($ws, $i18n, 'register'));
	}

	public function testGetTargetPageIdAllowsGuestToAccessDefaultPageWhenStartpageNotProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => null, 'username' => '']), [
			'password_protected' => '1',
			'password_protected_startpage' => '0',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame(DEFAULT_PAGE_ID, PageIdRouter::getTargetPageId($ws, $i18n, DEFAULT_PAGE_ID));
	}

	public function testGetTargetPageIdRedirectsGuestFromDefaultPageWhenStartpageProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => null, 'username' => '']), [
			'password_protected' => '1',
			'password_protected_startpage' => '1',
		]);
		$i18n = $this->mockI18n([
			'requireslogin_box_title' => 'T',
			'requireslogin_box_message' => 'M',
		]);
		$this->assertSame(LOGIN_PAGE_ID, PageIdRouter::getTargetPageId($ws, $i18n, DEFAULT_PAGE_ID));
	}

	public function testGetTargetPageIdDoesNotRedirectLoggedInUserWhenProtected(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => 'bob']), [
			'password_protected' => '1',
			'password_protected_startpage' => '1',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame('teams', PageIdRouter::getTargetPageId($ws, $i18n, 'teams'));
	}

	public function testGetTargetPageIdRedirectsTeamPageToLeaguesWhenNoIdGiven(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => 'bob']), [
			'password_protected' => '0',
		], null);
		$i18n = $this->mockI18n();
		$this->assertSame('leagues', PageIdRouter::getTargetPageId($ws, $i18n, 'team'));
	}

	public function testGetTargetPageIdKeepsTeamPageWhenIdGiven(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => 'bob']), [
			'password_protected' => '0',
		], '5');
		$i18n = $this->mockI18n();
		$this->assertSame('team', PageIdRouter::getTargetPageId($ws, $i18n, 'team'));
	}

	public function testGetTargetPageIdRedirectsUserWithEmptyUsernameToEnterUsername(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => '']), [
			'password_protected' => '0',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame(ENTERUSERNAME_PAGE_ID, PageIdRouter::getTargetPageId($ws, $i18n, 'home'));
	}

	public function testGetTargetPageIdDoesNotRedirectUserWithUsernameToEnterUsername(): void {
		$ws = $this->mockWebsoccerWithUser($this->makeUser(['id' => 1, 'username' => 'bob']), [
			'password_protected' => '0',
		]);
		$i18n = $this->mockI18n();
		$this->assertSame('home', PageIdRouter::getTargetPageId($ws, $i18n, 'home'));
	}
}
