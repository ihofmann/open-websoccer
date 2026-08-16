<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FreeClubsModel.
 */
final class FreeClubsModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new FreeClubsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsCountriesKey(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new FreeClubsModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('countries', $params);
		$this->assertSame([], $params['countries']);
	}
}
