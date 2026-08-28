<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for EntityLogDataService.
 */
final class EntityLogDataServiceTest extends TestCaseBase {
	public function testCreateInsertsEntityChangeRecord(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())
			->method('queryInsert')
			->with(
				[
					'created_date' => 123,
					'username' => 'admin',
					'ip' => '127.0.0.1',
					'type' => 'edit',
					'entity' => 'news',
					'entity_value' => '{"id":1}',
				],
				'ws_entitylog'
			);

		EntityLogDataService::create($ws, $db, 'edit', 'admin', 'news', '{"id":1}', 123, '127.0.0.1');
	}
}
