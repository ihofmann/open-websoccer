<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SendShoutBoxMessageController.
 */
final class SendShoutBoxMessageControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testInsertsMessageAndReturnsNull(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 9]));

		$inserts = [];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryInsert')->willReturnCallback(function ($columns, $table) use (&$inserts) {
			$inserts[] = [$columns, $table];
		});
		$db->method('queryDelete');

		$controller = new SendShoutBoxMessageController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['msgtext' => 'hello', 'id' => 3]));

		// First insert is the new message.
		$this->assertSame('ws_shoutmessage', $inserts[0][1]);
		$this->assertSame(9, $inserts[0][0]['user_id']);
		$this->assertSame('hello', $inserts[0][0]['message']);
		$this->assertSame(3, $inserts[0][0]['match_id']);
		$this->assertNotEmpty($inserts[0][0]['created_date']);
	}

	public function testDeletesOldMessagesOncePerSession(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 9]));

		$insertDate = null;
		$deletes = [];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryInsert')->willReturnCallback(function ($columns, $table) use (&$insertDate) {
			if ($table === 'ws_shoutmessage') {
				$insertDate = $columns['created_date'];
			}
		});
		$db->method('queryDelete')->willReturnCallback(function ($table, $where, $params) use (&$deletes) {
			$deletes[] = [$table, $where, $params];
		});

		$controller = new SendShoutBoxMessageController($i18n, $ws, $db);
		$controller->executeAction(['msgtext' => 'hi', 'id' => 1]);
		$controller->executeAction(['msgtext' => 'again', 'id' => 1]);

		// Old-message deletion runs only once (session flag set after first call).
		$this->assertCount(1, $deletes);
		$this->assertSame('ws_shoutmessage', $deletes[0][0]);
		// threshold = now - 14 days, derived from the insert's created_date.
		$this->assertSame($insertDate - 24 * 3600 * 14, $deletes[0][2]);
	}
}
