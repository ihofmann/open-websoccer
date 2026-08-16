<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LatestResultsBlockModel.
 */
final class LatestResultsBlockModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LatestResultsBlockModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsMatchesKey(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LatestResultsBlockModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('matches', $params);
		$this->assertSame([], $params['matches']);
	}
}
