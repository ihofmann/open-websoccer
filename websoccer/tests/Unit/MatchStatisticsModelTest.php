<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MatchStatisticsModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void {
		$this->assertTrue((new MatchStatisticsModel($this->mockDb(), $this->mockI18n(), $this->mockWebsoccer()))->renderView());
	}
	public function testMissingMatchIdThrows(): void {
		$this->expectException(Exception::class);
		(new MatchStatisticsModel($this->mockDb(), $this->mockI18n(), $this->mockWebsoccer()))->getTemplateParameters();
	}
}
