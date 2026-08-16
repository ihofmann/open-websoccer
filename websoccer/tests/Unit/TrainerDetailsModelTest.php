<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TrainerDetailsModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void { $this->assertTrue((new TrainerDetailsModel($this->mockDb(),$this->mockI18n(),$this->mockWebsoccer()))->renderView()); }
	public function testMissingTrainerThrows(): void { $this->expectException(Exception::class); (new TrainerDetailsModel($this->mockDb(),$this->mockI18n(),$this->mockWebsoccer()))->getTemplateParameters(); }
}
