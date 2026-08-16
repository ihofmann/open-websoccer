<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TrainingCampsDetailsModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void { $this->assertTrue((new TrainingCampsDetailsModel($this->mockDb(),$this->mockI18n(),$this->mockWebsoccer()))->renderView()); }
	public function testMissingCampThrows(): void { $this->expectException(Exception::class); (new TrainingCampsDetailsModel($this->mockDb(),$this->mockI18n(),$this->mockWebsoccer()))->getTemplateParameters(); }
}
