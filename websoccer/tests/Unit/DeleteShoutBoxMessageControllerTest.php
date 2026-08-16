<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DeleteShoutBoxMessageController.
 */
final class DeleteShoutBoxMessageControllerTest extends TestCaseBase {
	public function testExecuteActionDeletesMessageAndReturnsNull(): void {
		$captured = null;
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())->method('queryDelete')
			->willReturnCallback(function ($fromTable, $whereCondition, $parameters) use (&$captured) {
				$captured = ['fromTable' => $fromTable, 'whereCondition' => $whereCondition, 'parameters' => $parameters];
				return null;
			});

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);

		$controller = new DeleteShoutBoxMessageController($this->mockI18n(), $ws, $db);
		$this->assertNull($controller->executeAction(['mid' => 42]));

		$this->assertSame('ws_shoutmessage', $captured['fromTable']);
		$this->assertSame('id = %d', $captured['whereCondition']);
		$this->assertSame(42, $captured['parameters']);
	}

	public function testExecuteActionUsesMidParameter(): void {
		$seen = [];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryDelete')->willReturnCallback(function ($fromTable, $whereCondition, $parameters) use (&$seen) {
			$seen[] = $parameters;
			return null;
		});

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$controller = new DeleteShoutBoxMessageController($this->mockI18n(), $ws, $db);
		$controller->executeAction(['mid' => 7]);

		$this->assertSame([7], $seen);
	}
}
