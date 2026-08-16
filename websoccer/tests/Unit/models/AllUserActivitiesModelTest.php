<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AllUserActivitiesModel.
 */
final class AllUserActivitiesModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new AllUserActivitiesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsActivitiesKey(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new AllUserActivitiesModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('activities', $params);
		$this->assertSame([], $params['activities']);
	}
}
