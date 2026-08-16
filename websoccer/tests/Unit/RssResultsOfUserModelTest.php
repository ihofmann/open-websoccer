<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for RssResultsOfUserModel.
 */
final class RssResultsOfUserModelTest extends TestCaseBase {
	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new RssResultsOfUserModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyItemsWhenNoMatches(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new RssResultsOfUserModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['items']);
	}
}
