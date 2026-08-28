<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AdminLogDataService.
 */
final class AdminLogDataServiceTest extends TestCaseBase {
	public function testCreateInsertsLoginRecord(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())
			->method('queryInsert')
			->with(
				[
					'admin_name' => 'admin',
					'ip' => '127.0.0.1',
					'created_date' => 123,
				],
				'ws_adminlog'
			);

		AdminLogDataService::create($ws, $db, 'admin', '127.0.0.1', 123);
	}

	public function testDeleteOlderThanUsesProvidedThreshold(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())
			->method('queryDelete')
			->with('ws_adminlog', 'created_date < %d', 123);

		AdminLogDataService::deleteOlderThan($ws, $db, 123);
	}
}
