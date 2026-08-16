<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MyYouthTeamModelTest extends TestCaseBase {
	private function ws(bool $enabled=true): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws','youth_enabled'=>$enabled]); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewReflectsConfiguration(): void { $this->assertTrue((new MyYouthTeamModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); $this->assertFalse((new MyYouthTeamModel($this->mockDb(),$this->mockI18n(),$this->ws(false)))->renderView()); }
	public function testNoClubReturnsEmptyPlayers(): void { $p=(new MyYouthTeamModel($this->mockDb(),$this->mockI18n(),$this->ws()))->getTemplateParameters(); $this->assertSame([], $p['players']); }
}
