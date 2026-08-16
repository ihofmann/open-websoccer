<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MessageDetailsModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void {
		$this->assertTrue((new MessageDetailsModel($this->mockDb(), $this->mockI18n(), $this->mockWebsoccer()))->renderView());
	}
	public function testEmptyMessageIsReturned(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$params = (new MessageDetailsModel($this->mockDb(), $this->mockI18n(), $ws))->getTemplateParameters();
		$this->assertArrayHasKey('message', $params);
		$this->assertNull($params['message']);
	}
}
