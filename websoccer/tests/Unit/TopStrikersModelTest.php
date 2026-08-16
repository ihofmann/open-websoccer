<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TopStrikersModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void { $this->assertTrue((new TopStrikersModel($this->mockDb(),$this->mockI18n(),$this->mockWebsoccer()))->renderView()); }
	public function testReturnsPlayersAndLeagues(): void { $ws=$this->mockWebsoccer(['db_prefix'=>'ws']); $p=(new TopStrikersModel($this->mockDb(),$this->mockI18n(),$ws))->getTemplateParameters(); $this->assertArrayHasKey('players',$p); $this->assertArrayHasKey('leagues',$p); }
}
