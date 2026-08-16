<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for HighscoreModel.
 */
final class HighscoreModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'entries_per_page' => 20]);
		$model = new HighscoreModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsUsersAndPaginator(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'entries_per_page' => 20]);
		$model = new HighscoreModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('users', $params);
		$this->assertArrayHasKey('paginator', $params);
		$this->assertSame([], $params['users']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}
}
