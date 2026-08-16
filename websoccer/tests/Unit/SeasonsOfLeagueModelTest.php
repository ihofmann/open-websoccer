<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SeasonsOfLeagueModel.
 */
final class SeasonsOfLeagueModelTest extends TestCaseBase {
	private function ws(array $config, $requestCb): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	private function dbMock(array $rows = [], int $aggValue = 0): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows, $aggValue) {
			$cols = is_string($columns) ? $columns : '';
			if (preg_match('/(COUNT|MAX|MIN|SUM|AVG)\s*\(/i', $cols)) {
				$alias = 'hits';
				if (preg_match('/AS\s+`?(\w+)`?/i', $cols, $m)) $alias = $m[1];
				return $this->dbResult([[$alias => $aggValue]]);
			}
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsFalseWhenNoLeagueId(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function() { return null; });
		$model = new SeasonsOfLeagueModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenLeagueIdProvided(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function($name) { return ($name === 'leagueid') ? 3 : null; });
		$model = new SeasonsOfLeagueModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsSeasonsAndLeagueName(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function($name) { return ($name === 'leagueid') ? 3 : null; });
		$rows = [['id' => 1, 'name' => '2024', 'league_name' => 'Premier']];
		$model = new SeasonsOfLeagueModel($this->dbMock($rows), $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['seasons']);
		$this->assertSame('Premier', $params['league_name']);
		$this->assertSame(0, $params['currentMatchDay']);
		$this->assertSame(0, $params['maxMatchDay']);
	}
}
