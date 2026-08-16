<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MyScheduleModelTest extends TestCaseBase {
	private function ws(): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws','entries_per_page'=>10]); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewIsTrue(): void { $this->assertTrue((new MyScheduleModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); }
	public function testEmptyScheduleHasNoMatches(): void { $p=(new MyScheduleModel($this->mockDb(),$this->mockI18n(),$this->ws()))->getTemplateParameters(); $this->assertSame([], $p['matches']); $this->assertNull($p['paginator']); }
}
