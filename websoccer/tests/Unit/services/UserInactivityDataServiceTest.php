<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserInactivityDataService.
 */
final class UserInactivityDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'gravatar_enable' => 0,
			'context_root' => '',
		]);
	}

	public function testGetUserInactivityReturnsExistingRow(): void {
		$row = ['id' => 1, 'user_id' => 5, 'login' => 10, 'login_check' => 100, 'tactics' => 5,
			'transfer' => 3, 'transfer_check' => 100, 'contractextensions' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, UserInactivityDataService::getUserInactivity($this->ws, $db, 5));
	}

	public function testGetUserInactivityCreatesEntryWhenNotFound(): void {
		$row = ['id' => 2, 'user_id' => 5, 'login' => 0, 'login_check' => 0, 'tactics' => 0,
			'transfer' => 0, 'transfer_check' => 0, 'contractextensions' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([]),
			$this->dbResult([$row])
		);
		$db->expects($this->once())->method('queryInsert')
			->with($this->callback(function ($cols) {
				return isset($cols['user_id']) && $cols['user_id'] == 5
					&& isset($cols['login_last']) && $cols['login_last'] === 0
					&& isset($cols['login_check']) && $cols['login_check'] === 0
					&& isset($cols['transfer_check']) && $cols['transfer_check'] === 0;
			}), $this->anything());
		$this->assertSame($row, UserInactivityDataService::getUserInactivity($this->ws, $db, 5));
	}

	public function testResetContractExtensionFieldUpdatesToZero(): void {
		$row = ['id' => 7, 'user_id' => 5, 'contractextensions' => 30, 'login_check' => 0, 'transfer_check' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$captured = null;
		$db->method('queryUpdate')->willReturnCallback(function ($cols, $table, $where, $params) use (&$captured) {
			$captured = ['cols' => $cols, 'params' => $params];
		});
		UserInactivityDataService::resetContractExtensionField($this->ws, $db, 5);
		$this->assertSame(0, $captured['cols']['vertragsauslauf']);
		$this->assertSame(7, $captured['params']);
	}

	public function testIncreaseContractExtensionFieldAddsFive(): void {
		$row = ['id' => 7, 'user_id' => 5, 'contractextensions' => 10, 'login_check' => 0, 'transfer_check' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$captured = null;
		$db->method('queryUpdate')->willReturnCallback(function ($cols) use (&$captured) {
			$captured = $cols;
		});
		UserInactivityDataService::increaseContractExtensionField($this->ws, $db, 5);
		$this->assertSame(15, $captured['vertragsauslauf']);
	}

	public function testIncreaseContractExtensionFieldCapsAtHundred(): void {
		$row = ['id' => 7, 'user_id' => 5, 'contractextensions' => 98, 'login_check' => 0, 'transfer_check' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$captured = null;
		$db->method('queryUpdate')->willReturnCallback(function ($cols) use (&$captured) {
			$captured = $cols;
		});
		UserInactivityDataService::increaseContractExtensionField($this->ws, $db, 5);
		// 98 + 5 = 103, capped to 100
		$this->assertSame(100, $captured['vertragsauslauf']);
	}

	public function testComputeUserInactivityDoesNotUpdateWhenChecksAreRecent(): void {
		$now = time();
		$inactivityRow = ['id' => 7, 'user_id' => 5, 'login' => 0, 'login_check' => $now,
			'tactics' => 0, 'transfer' => 0, 'transfer_check' => $now, 'contractextensions' => 0];
		$userRow = ['id' => 5, 'lastonline' => $now, 'registration_date' => $now, 'picture' => '', 'email' => ''];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$inactivityRow]),
			$this->dbResult([$userRow])
		);
		$db->expects($this->never())->method('queryUpdate');
		UserInactivityDataService::computeUserInactivity($this->ws, $db, 5);
	}

	public function testComputeUserInactivityUpdatesWhenChecksAreOutdated(): void {
		$now = time();
		$inactivityRow = ['id' => 7, 'user_id' => 5, 'login' => 0, 'login_check' => 0,
			'tactics' => 0, 'transfer' => 0, 'transfer_check' => 0, 'contractextensions' => 0];
		$userRow = ['id' => 5, 'lastonline' => $now - 10 * 24 * 3600, 'registration_date' => $now - 30 * 24 * 3600,
			'picture' => '', 'email' => ''];
		$formationRow = ['date' => $now - 5 * 24 * 3600];
		// getLatestBidOfUser returns false (no bid)
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$inactivityRow]),  // getUserInactivity
			$this->dbResult([$userRow]),        // getUserById
			$this->dbResult([$formationRow]),   // formation query
			$this->dbResult([])                 // getLatestBidOfUser -> false
		);
		$captured = null;
		$db->method('queryUpdate')->willReturnCallback(function ($cols, $table, $where, $params) use (&$captured) {
			$captured = ['cols' => $cols, 'params' => $params];
		});
		UserInactivityDataService::computeUserInactivity($this->ws, $db, 5);
		$this->assertNotNull($captured);
		$this->assertArrayHasKey('login', $captured['cols']);
		$this->assertArrayHasKey('login_check', $captured['cols']);
		$this->assertArrayHasKey('aufstellung', $captured['cols']);
		$this->assertArrayHasKey('transfer', $captured['cols']);
		$this->assertArrayHasKey('transfer_check', $captured['cols']);
		$this->assertSame(7, $captured['params']);
	}
}
