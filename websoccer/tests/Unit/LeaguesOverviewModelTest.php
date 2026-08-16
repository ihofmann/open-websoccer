<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LeaguesOverviewModel.
 */
final class LeaguesOverviewModelTest extends TestCaseBase {
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
		$model = new LeaguesOverviewModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyCountriesWhenNoData(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LeaguesOverviewModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('countries', $params);
		$this->assertSame([], $params['countries']);
	}

	public function testGetTemplateParametersReturnsCountriesFromDb(): void {
		$rows = [
			['name' => 'England', 'noOfLeagues' => 4],
			['name' => 'Germany', 'noOfLeagues' => 3],
		];
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new LeaguesOverviewModel($this->dbMock(['_liga' => $rows]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['countries']);
		$this->assertSame($rows, $params['countries']);
	}
}
