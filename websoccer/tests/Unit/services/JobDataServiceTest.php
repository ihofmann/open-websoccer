<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for JobDataService.
 */
final class JobDataServiceTest extends TestCaseBase {
	public function testGetJobReadsDatabaseDefinition(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$row = ['id' => 'sim', 'class' => 'SimulateMatchesJob', 'interval' => 1];
		$db->expects($this->once())->method('querySelect')
			->with('*', 'ws_jobs', 'id = \'%s\'', 'sim', 1)
			->willReturn($this->dbResult([$row]));

		$this->assertSame($row, JobDataService::getJob($ws, $db, 'sim'));
	}

	public function testUpdateJobWritesRuntimeState(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())->method('queryUpdate')
			->with(['last_ping' => 123], 'ws_jobs', 'id = \'%s\'', 'sim');

		JobDataService::updateJob($ws, $db, 'sim', ['last_ping' => 123]);
	}
}
