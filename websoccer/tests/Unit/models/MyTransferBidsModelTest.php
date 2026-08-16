<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MyTransferBidsModelTest extends TestCaseBase {
	private function ws(): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws']); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewIsTrue(): void { $this->assertTrue((new MyTransferBidsModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); }
	public function testReturnsBidsKey(): void { $p=(new MyTransferBidsModel($this->mockDb(),$this->mockI18n(),$this->ws()))->getTemplateParameters(); $this->assertArrayHasKey('bids',$p); }
}
