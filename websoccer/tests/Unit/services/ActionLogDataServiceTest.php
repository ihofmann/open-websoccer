<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for ActionLogDataService.
 */
final class ActionLogDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(['db_prefix' => 'ws']);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetActionLogsOfUserReturnsRows(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['log_id' => '1', 'action_id' => 'login', 'user_id' => '7', 'created_date' => '100', 'user_name' => 'joe'],
			['log_id' => '2', 'action_id' => 'logout', 'user_id' => '7', 'created_date' => '200', 'user_name' => 'joe'],
		]));
		$logs = ActionLogDataService::getActionLogsOfUser($ws, $db, 7, 10);
		$this->assertCount(2, $logs);
		$this->assertSame('login', $logs[0]['action_id']);
	}

	public function testGetActionLogsOfUserReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], ActionLogDataService::getActionLogsOfUser($ws, $db, 7));
	}

	public function testGetLatestActionLogsReturnsRows(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['log_id' => '3', 'action_id' => 'transfer', 'user_id' => '8', 'created_date' => '300', 'user_name' => 'amy'],
		]));
		$logs = ActionLogDataService::getLatestActionLogs($ws, $db, 5);
		$this->assertCount(1, $logs);
		$this->assertSame('amy', $logs[0]['user_name']);
	}

	public function testGetLatestActionLogsReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], ActionLogDataService::getLatestActionLogs($ws, $db));
	}

	public function testCreateOrUpdateActionLogUpdatesWhenRecentLogExists(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// 1st querySelect: recent log exists.
		$db->method('querySelect')->willReturn($this->dbResult([['id' => '42']]));
		$db->expects($this->once())->method('queryDelete');
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->never())->method('queryInsert');

		ActionLogDataService::createOrUpdateActionLog($ws, $db, 7, 'login');
	}

	public function testCreateOrUpdateActionLogInsertsWhenNoRecentLog(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->once())->method('queryDelete');
		$db->expects($this->never())->method('queryUpdate');
		$db->expects($this->once())->method('queryInsert');

		ActionLogDataService::createOrUpdateActionLog($ws, $db, 7, 'login');
	}
}
