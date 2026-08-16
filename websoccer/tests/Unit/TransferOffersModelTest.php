<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TransferOffersModelTest extends TestCaseBase {
	private function ws($enabled): \WebSoccer { $ws=$this->mockWebsoccer(['transferoffers_enabled'=>$enabled,'db_prefix'=>'ws','entries_per_page'=>10]); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewReflectsConfiguration(): void { $this->assertTrue((new TransferOffersModel($this->mockDb(),$this->mockI18n(),$this->ws(1)))->renderView()); $this->assertFalse((new TransferOffersModel($this->mockDb(),$this->mockI18n(),$this->ws(0)))->renderView()); }
	public function testReturnsOffersAndPaginator(): void { $ws=$this->ws(1); $p=(new TransferOffersModel($this->mockDb(),$this->mockI18n(),$ws))->getTemplateParameters(); $this->assertArrayHasKey('offers',$p); $this->assertArrayHasKey('paginator',$p); }
}
