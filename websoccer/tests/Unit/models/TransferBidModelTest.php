<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TransferBidModelTest extends TestCaseBase {
	public function testInvalidPlayerIdThrows(): void { $this->expectException(Exception::class); (new TransferBidModel($this->mockDb(),$this->mockI18n(),$this->mockWebsoccer()))->renderView(); }
	public function testRenderViewRequiresPositiveId(): void { $ws=$this->mockWebsoccer(); $ws->method('getRequestParameter')->willReturn(0); $this->expectException(Exception::class); (new TransferBidModel($this->mockDb(),$this->mockI18n(),$ws))->renderView(); }
}
