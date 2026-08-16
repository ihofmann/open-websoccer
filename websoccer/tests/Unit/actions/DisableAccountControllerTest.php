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
 * Unit tests for DisableAccountController.
 */
final class DisableAccountControllerTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		\FakeSessionAuthForTests::$logoutCalls = 0;
	}

	private function makeUserWithClub(?int $clubId): \User {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager']);
		if ($clubId !== null) {
			$user->setClubId($clubId);
		}
		return $user;
	}

	public function testExecuteActionThrowsWhenAuthenticatorClassNotFound(): void {
		$ws = $this->mockWebsoccer(['authentication_mechanism' => 'NonExistentAuthXyz', 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DisableAccountController($this->mockI18n(), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Class not found: NonExistentAuthXyz');
		$controller->executeAction([]);
	}

	public function testExecuteActionDisablesUserWithClubAndLogsOut(): void {
		$updates = [];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updates) {
			$updates[] = ['columns' => $columns, 'fromTable' => $fromTable, 'parameters' => $parameters];
			return null;
		});

		$ws = $this->mockWebsoccer(['authentication_mechanism' => 'FakeSessionAuthForTests', 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DisableAccountController(
			$this->mockI18n(['cancellation_success' => 'disabled']), $ws, $db);

		$this->assertSame('home', $controller->executeAction([]));
		// first update clears the club, second disables the user account
		$this->assertCount(2, $updates);
		$this->assertSame('ws_verein', $updates[0]['fromTable']);
		$this->assertSame('0', $updates[1]['columns']['status']);
		$this->assertSame('ws_user', $updates[1]['fromTable']);
		$this::assertSame(1, \FakeSessionAuthForTests::$logoutCalls);
	}

	public function testExecuteActionDisablesUserWithoutClub(): void {
		$updates = [];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updates) {
			$updates[] = ['columns' => $columns, 'fromTable' => $fromTable];
			return null;
		});
		// getClubId() runs a query when the user has no club yet; return empty set.
		$db->method('querySelect')->willReturn($this->dbResult([]));

		$ws = $this->mockWebsoccer(['authentication_mechanism' => 'FakeSessionAuthForTests', 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(null)); // no club

		$controller = new DisableAccountController(
			$this->mockI18n(['cancellation_success' => 'disabled']), $ws, $db);

		$this->assertSame('home', $controller->executeAction([]));
		// only the user-account update (no club update since no club)
		$this->assertCount(1, $updates);
		$this->assertSame('ws_user', $updates[0]['fromTable']);
	}
}
