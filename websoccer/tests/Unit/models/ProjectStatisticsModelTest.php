<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for ProjectStatisticsModel.
 */
final class ProjectStatisticsModelTest extends TestCaseBase {
	private function dbMock(int $hits): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($hits) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $hits]]);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new ProjectStatisticsModel($this->dbMock(0), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsZeroCountsWhenEmpty(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new ProjectStatisticsModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(0, $params['usersOnline']);
		$this->assertSame(0, $params['usersTotal']);
		$this->assertSame(0, $params['numberOfLeagues']);
		$this->assertSame(0, $params['numberOfFreeTeams']);
	}

	public function testGetTemplateParametersReturnsCountsWhenUsersOnline(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new ProjectStatisticsModel($this->dbMock(3), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(3, $params['usersOnline']);
		$this->assertSame(3, $params['usersTotal']);
	}
}
