<?php
use OpenWebSoccer\Tests\TestCaseBase;

if (!class_exists('FakeSessionAuthForTests')) {
	class FakeSessionAuthForTests implements \IUserAuthentication {
		public static int $logoutCalls = 0;
		public function __construct(\WebSoccer $website) {}
		public function verifyAndUpdateCurrentUser(\User $currentUser) {}
		public function logoutUser(\User $currentUser): void {
			self::$logoutCalls++;
		}
	}
}

/**
 * Unit tests for LogoutController.
 */
final class LogoutControllerTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		\FakeSessionAuthForTests::$logoutCalls = 0;
	}

	public function testExecuteActionThrowsWhenAuthenticatorClassNotFound(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['authentication_mechanism' => 'NonExistentAuthXyz', 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new LogoutController($this->mockI18n(), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Class not found: NonExistentAuthXyz');
		$controller->executeAction([]);
	}

	public function testExecuteActionLogsOutUserAndReturnsHome(): void {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager']);
		$ws = $this->mockWebsoccer(['authentication_mechanism' => 'FakeSessionAuthForTests', 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new LogoutController(
			$this->mockI18n(['logout_message_title' => 'logged out']), $ws, $this->mockDb());

		$this->assertSame('home', $controller->executeAction([]));
		$this->assertSame(1, \FakeSessionAuthForTests::$logoutCalls);
	}

	public function testExecuteActionLogsOutViaMultipleAuthenticators(): void {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager']);
		$ws = $this->mockWebsoccer(['authentication_mechanism' => 'FakeSessionAuthForTests, FakeSessionAuthForTests', 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new LogoutController($this->mockI18n(['logout_message_title' => 'logged out']), $ws, $this->mockDb());

		$this->assertSame('home', $controller->executeAction([]));
		$this->assertSame(2, \FakeSessionAuthForTests::$logoutCalls);
	}
}
