<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MessagesOutboxModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void {
		$this->assertTrue((new MessagesOutboxModel($this->mockDb(), $this->mockI18n(), $this->mockWebsoccer()))->renderView());
	}
	public function testEmptyOutboxHasEmptyMessages(): void {
		$ws = $this->mockWebsoccer(['entries_per_page' => 10, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$params = (new MessagesOutboxModel($this->mockDb(), $this->mockI18n(), $ws))->getTemplateParameters();
		$this->assertSame([], $params['messages']);
		$this->assertArrayHasKey('paginator', $params);
	}
}
