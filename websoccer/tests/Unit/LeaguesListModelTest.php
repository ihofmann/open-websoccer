<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LeaguesListModel.
 */
final class LeaguesListModelTest extends TestCaseBase {
	private function dbMock(array $selectMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($selectMap) {
			foreach ($selectMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $this->dbResult($rows);
				}
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LeaguesListModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyCountriesWhenNoData(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LeaguesListModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('countries', $params);
		$this->assertSame([], $params['countries']);
	}

	public function testGetTemplateParametersGroupsLeaguesByCountry(): void {
		$rows = [
			['id' => 1, 'country' => 'England', 'name' => 'Premier'],
			['id' => 2, 'country' => 'England', 'name' => 'Championship'],
			['id' => 3, 'country' => 'Germany', 'name' => 'Bundesliga'],
		];
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LeaguesListModel($this->dbMock(['_liga' => $rows]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['countries']['England']);
		$this->assertCount(1, $params['countries']['Germany']);
	}
}
