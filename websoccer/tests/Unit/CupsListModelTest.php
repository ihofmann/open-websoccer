<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CupsListModel.
 */
final class CupsListModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new CupsListModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsCupsKey(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new CupsListModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('cups', $params);
		$this->assertSame([], $params['cups']);
	}
}
