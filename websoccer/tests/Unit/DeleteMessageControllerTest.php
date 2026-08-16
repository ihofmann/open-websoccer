<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DeleteMessageController.
 */
final class DeleteMessageControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($selectRowsByTable) {
				foreach ($selectRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		return $db;
	}

	public function testExecuteActionThrowsWhenMessageNotFound(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new DeleteMessageController(
			$this->mockI18n(['messages_delete_invalidid' => 'invalid id']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid id');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionDeletesMessageAndReturnsNull(): void {
		$deleted = false;
		$db = $this->makeDb(['_briefe' => [['message_id' => 5, 'subject' => 'Hi']]]);
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new DeleteMessageController(
			$this->mockI18n(['messages_delete_success' => 'deleted']), $ws, $db);

		$this->assertNull($controller->executeAction(['id' => 5]));
		$this->assertTrue($deleted);
	}
}
