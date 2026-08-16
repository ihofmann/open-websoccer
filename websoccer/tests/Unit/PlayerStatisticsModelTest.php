<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PlayerStatisticsModel.
 */
final class PlayerStatisticsModelTest extends TestCaseBase {
	private function ws(array $config, $requestValue = null): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback(function() use ($requestValue) { return $requestValue; });
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	private function dbWithRows(array $rows): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$model = new PlayerStatisticsModel($this->dbWithRows([]), $this->mockI18n(), $this->ws(['db_prefix' => 'ws']));
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenIdInvalid(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 0);
		$model = new PlayerStatisticsModel($this->dbWithRows([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'nf']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersSplitsLeagueAndCupStatistics(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$rows = [
			['league_name' => 'Premier', 'season_name' => '2024', 'cup_name' => '', 'matches' => 10],
			['league_name' => '', 'season_name' => '', 'cup_name' => 'FA Cup', 'matches' => 2],
		];
		$model = new PlayerStatisticsModel($this->dbWithRows($rows), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['leagueStatistics']);
		$this->assertSame('Premier', $params['leagueStatistics'][0]['league_name']);
		$this->assertCount(1, $params['cupStatistics']);
		$this->assertSame('FA Cup', $params['cupStatistics'][0]['cup_name']);
	}

	public function testGetTemplateParametersReturnsEmptyStatisticsWhenNoData(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$model = new PlayerStatisticsModel($this->dbWithRows([]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['leagueStatistics']);
		$this->assertSame([], $params['cupStatistics']);
	}
}
