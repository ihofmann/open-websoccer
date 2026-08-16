<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MatchPlayersModelTest extends TestCaseBase {
	public function testRenderViewIsTrue(): void {
		$this->assertTrue((new MatchPlayersModel($this->mockDb(), $this->mockI18n(), $this->mockWebsoccer()))->renderView());
	}
	public function testMissingMatchIdThrows(): void {
		$ws = $this->mockWebsoccer();
		$this->expectException(Exception::class);
		(new MatchPlayersModel($this->mockDb(), $this->mockI18n(), $ws))->getTemplateParameters();
	}
}
