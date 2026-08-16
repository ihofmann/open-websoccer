<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SendMessageController.
 */
final class SendMessageControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'messages_enabled' => TRUE,
			'messages_break_minutes' => 10,
		];
	}

	public function testThrowsWhenMessagesDisabled(): void {
		$i18n = $this->mockI18n(['messages_err_messagesdisabled' => 'disabled']);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['messages_enabled' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('disabled');

		$controller = new SendMessageController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'friend', 'subject' => 'Hi', 'msgcontent' => 'Hello']);
	}

	public function testThrowsWhenRecipientInvalid(): void {
		$i18n = $this->mockI18n(['messages_send_err_invalidrecipient' => 'invalid recipient']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		// getUserIdByNick -> no row -> -1.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('invalid recipient');

		$controller = new SendMessageController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'ghost', 'subject' => 'Hi', 'msgcontent' => 'Hello']);
	}

	public function testThrowsWhenSendingToSelf(): void {
		$i18n = $this->mockI18n(['messages_send_err_sendtoyourself' => 'to yourself']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->makeDb([], ['ws_user' => [['id' => 1]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('to yourself');

		$controller = new SendMessageController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'me', 'subject' => 'Hi', 'msgcontent' => 'Hello']);
	}

	public function testThrowsWhenTimebreakViolated(): void {
		$i18n = $this->mockI18n(['messages_send_err_timebreak' => 'too soon %s']);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		// Recipient 5; last message was sent "now" -> within the break window.
		$db = $this->makeDb([], [
			'ws_user' => [['id' => 5]],
			'ws_briefe' => [['date' => 1000000]],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('too soon');

		$controller = new SendMessageController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'friend', 'subject' => 'Hi', 'msgcontent' => 'Hello']);
	}
}
