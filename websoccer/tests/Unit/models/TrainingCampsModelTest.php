<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TrainingCampsModelTest extends TestCaseBase {
	private function ws(): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws']); $ws->method('getUser')->willReturn($this->makeUser(['id'=>1])); return $ws; }
	public function testRenderViewIsTrue(): void { $this->assertTrue((new TrainingCampsModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); }
	public function testNoClubThrowsFeatureException(): void { $this->expectException(Exception::class); (new TrainingCampsModel($this->mockDb(),$this->mockI18n(),$this->ws()))->getTemplateParameters(); }
}
