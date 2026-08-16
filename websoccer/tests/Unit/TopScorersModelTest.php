<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TopScorersModel.
 */
final class TopScorersModelTest extends TestCaseBase {
	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new TopScorersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyPlayersAndLeaguesWhenNoData(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new TopScorersModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['players']);
		$this->assertSame([], $params['leagues']);
	}
}
