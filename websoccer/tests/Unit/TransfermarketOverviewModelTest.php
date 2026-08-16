<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TransfermarketOverviewModelTest extends TestCaseBase {
	private function ws($enabled): \WebSoccer { $ws=$this->mockWebsoccer(['transfermarket_enabled'=>$enabled,'db_prefix'=>'ws','entries_per_page'=>10]); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewReflectsConfiguration(): void { $this->assertTrue((new TransfermarketOverviewModel($this->mockDb(),$this->mockI18n(),$this->ws(1)))->renderView()); $this->assertFalse((new TransfermarketOverviewModel($this->mockDb(),$this->mockI18n(),$this->ws(0)))->renderView()); }
	public function testGuestCannotLoadParameters(): void { $this->expectException(Exception::class); (new TransfermarketOverviewModel($this->mockDb(),$this->mockI18n(),$this->ws(1)))->getTemplateParameters(); }
}
