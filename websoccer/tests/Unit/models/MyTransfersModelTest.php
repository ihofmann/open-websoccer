<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MyTransfersModelTest extends TestCaseBase {
	private function ws(): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws']); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewIsTrue(): void { $this->assertTrue((new MyTransfersModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); }
	public function testReturnsCompletedTransfersKey(): void { $p=(new MyTransfersModel($this->mockDb(),$this->mockI18n(),$this->ws()))->getTemplateParameters(); $this->assertArrayHasKey('completedtransfers',$p); }
}
