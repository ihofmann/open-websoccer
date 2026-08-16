<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TeamDetailsModel.
 */
final class TeamDetailsModelTest extends TestCaseBase {
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

	private function dbMock(array $cached = [], array $playerFacts = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($playerFacts) {
			if (is_string($fromTable) && stripos($fromTable, '_spieler') !== false) {
				return $this->dbResult([$playerFacts]);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	private function config(): array {
		return ['db_prefix' => 'ws', 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => 0];
	}

	private function teamRow(): array {
		return ['team_id' => 1, 'team_name' => 'FC Test', 'team_league_id' => 2, 'is_nationalteam' => '0'];
	}

	public function testRenderViewReturnsTrue(): void {
		$model = new TeamDetailsModel($this->dbMock(), $this->mockI18n(), $this->ws($this->config()));
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenIdInvalid(): void {
		$ws = $this->ws($this->config(), 0);
		$model = new TeamDetailsModel($this->dbMock(), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'nf']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersThrowsWhenTeamNotFound(): void {
		$ws = $this->ws($this->config(), 5);
		$model = new TeamDetailsModel($this->dbMock([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'nf']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsTeamStadiumAndPlayerFacts(): void {
		$ws = $this->ws($this->config(), 5);
		$playerFacts = ['numberOfPlayers' => 0, 'avgAge' => 0, 'sumMarketValue' => 0];
		$model = new TeamDetailsModel($this->dbMock([$this->teamRow()], $playerFacts), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(1, $params['team']['team_id']);
		$this->assertFalse($params['stadium']);
		$this->assertSame(0, $params['playerfacts']['numberOfPlayers']);
		$this->assertSame(0, $params['playerfacts']['avgMarketValue']);
	}
}
