<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for MessagesDataService.
 */
final class MessagesDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(int $userId = 7): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => $userId]));
		return $ws;
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	private function messageRow(array $overrides = []): array {
		return array_merge([
			'message_id' => '1', 'subject' => 'Hello', 'content' => 'World', 'date' => '100', 'seen' => '0',
			'recipient_id' => '7', 'recipient_name' => 'joe', 'sender_id' => '8', 'sender_name' => 'amy',
		], $overrides);
	}

	public function testGetInboxMessagesReturnsMessages(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([$this->messageRow()]));
		$messages = MessagesDataService::getInboxMessages($ws, $db, 0, 10);
		$this->assertCount(1, $messages);
		$this->assertSame('Hello', $messages[0]['subject']);
	}

	public function testGetInboxMessagesReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], MessagesDataService::getInboxMessages($ws, $db, 0, 10));
	}

	public function testGetOutboxMessagesReturnsMessages(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([
			$this->messageRow(['message_id' => '2', 'subject' => 'Sent']),
		]));
		$messages = MessagesDataService::getOutboxMessages($ws, $db, 0, 10);
		$this->assertSame('Sent', $messages[0]['subject']);
	}

	public function testGetMessageByIdReturnsMessageWhenFound(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([$this->messageRow(['message_id' => '5'])]));
		$message = MessagesDataService::getMessageById($ws, $db, 5);
		$this->assertNotNull($message);
		$this->assertSame('5', $message['message_id']);
	}

	public function testGetMessageByIdReturnsNullWhenNotFound(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertNull(MessagesDataService::getMessageById($ws, $db, 999));
	}

	public function testGetLastMessageOfUserIdReturnsMessageWhenFound(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([$this->messageRow(['message_id' => '9'])]));
		$message = MessagesDataService::getLastMessageOfUserId($ws, $db, 8);
		$this->assertSame('9', $message['message_id']);
	}

	public function testGetLastMessageOfUserIdReturnsNullWhenNone(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertNull(MessagesDataService::getLastMessageOfUserId($ws, $db, 8));
	}

	public function testCountInboxMessagesReturnsCount(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([['hits' => '4']]));
		$this->assertSame('4', MessagesDataService::countInboxMessages($ws, $db));
	}

	public function testCountInboxMessagesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, MessagesDataService::countInboxMessages($ws, $db));
	}

	public function testCountUnseenInboxMessagesReturnsCount(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([['hits' => '2']]));
		$this->assertSame('2', MessagesDataService::countUnseenInboxMessages($ws, $db));
	}

	public function testCountUnseenInboxMessagesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, MessagesDataService::countUnseenInboxMessages($ws, $db));
	}

	public function testCountOutboxMessagesReturnsCount(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([['hits' => '6']]));
		$this->assertSame('6', MessagesDataService::countOutboxMessages($ws, $db));
	}

	public function testCountOutboxMessagesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer(7);
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, MessagesDataService::countOutboxMessages($ws, $db));
	}
}
