<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PlayerDetailsWithDependenciesModel.
 */
final class PlayerDetailsWithDependenciesModelTest extends TestCaseBase {
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

	private function dbWithCachedPlayer(array $cached, array $gradeRows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($gradeRows) {
			if (is_string($fromTable) && stripos($fromTable, '_spiel_berechnung') !== false) {
				return $this->dbResult($gradeRows);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	private function config(): array {
		return ['db_prefix' => 'ws', 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => 0];
	}

	private function playerRow(): array {
		return [
			'player_id' => 5, 'player_position' => 'Abwehr', 'player_nationality' => '',
			'player_marketvalue' => 2000, 'matches_info' => '2.5;3'
		];
	}

	public function testRenderViewReturnsTrue(): void {
		$model = new PlayerDetailsWithDependenciesModel($this->dbWithCachedPlayer([]), $this->mockI18n(), $this->ws($this->config()));
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenIdInvalid(): void {
		$ws = $this->ws($this->config(), 0);
		$model = new PlayerDetailsWithDependenciesModel($this->dbWithCachedPlayer([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'nf']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsPlayerGradesAndTransfers(): void {
		$ws = $this->ws($this->config(), 5);
		$model = new PlayerDetailsWithDependenciesModel($this->dbWithCachedPlayer([$this->playerRow()]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(5, $params['player']['player_id']);
		$this->assertSame([], $params['grades']);
		$this->assertSame([], $params['completedtransfers']);
	}

	public function testGetTemplateParametersReturnsGradesFromDb(): void {
		$ws = $this->ws($this->config(), 5);
		$grades = [['grade' => 2], ['grade' => 3]];
		$model = new PlayerDetailsWithDependenciesModel($this->dbWithCachedPlayer([$this->playerRow()], $grades), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		// grades are reversed in the model
		$this->assertSame([3, 2], $params['grades']);
	}
}
