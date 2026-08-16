<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MyTeamModelTest extends TestCaseBase {
	private function ws(): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws']); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewIsTrue(): void { $this->assertTrue((new MyTeamModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); }
	public function testNoClubReturnsEmptyPlayers(): void { $p=(new MyTeamModel($this->mockDb(),$this->mockI18n(),$this->ws()))->getTemplateParameters(); $this->assertSame([], $p['players']); $this->assertArrayHasKey('captain_id',$p); }
}
