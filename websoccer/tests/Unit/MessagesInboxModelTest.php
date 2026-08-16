<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MessagesInboxModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void {
		$this->assertTrue((new MessagesInboxModel($this->mockDb(), $this->mockI18n(), $this->mockWebsoccer()))->renderView());
	}
	public function testEmptyInboxHasEmptyMessages(): void {
		$ws = $this->mockWebsoccer(['entries_per_page' => 10, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$params = (new MessagesInboxModel($this->mockDb(), $this->mockI18n(), $ws))->getTemplateParameters();
		$this->assertSame([], $params['messages']);
		$this->assertArrayHasKey('paginator', $params);
	}
}
