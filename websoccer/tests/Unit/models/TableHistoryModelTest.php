<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TableHistoryModel.
 */
final class TableHistoryModelTest extends TestCaseBase {
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

	private function dbMock(array $cached = [], int $aggValue = 0): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($aggValue) {
			$cols = is_string($columns) ? $columns : '';
			if (preg_match('/(COUNT|MAX|MIN|SUM|AVG)\s*\(/i', $cols)) {
				$alias = 'hits';
				if (preg_match('/AS\s+`?(\w+)`?/i', $cols, $m)) $alias = $m[1];
				return $this->dbResult([[$alias => $aggValue]]);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$model = new TableHistoryModel($this->dbMock(), $this->mockI18n(), $this->ws(['db_prefix' => 'ws']));
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenIdInvalid(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 0);
		$model = new TableHistoryModel($this->dbMock(), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'nf']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersThrowsWhenTeamNotFound(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$model = new TableHistoryModel($this->dbMock([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'nf']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsTeamNameAndEmptyHistory(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$team = ['team_id' => 1, 'team_name' => 'FC Test', 'team_league_id' => 2, 'is_nationalteam' => '0'];
		$model = new TableHistoryModel($this->dbMock([$team], 0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('FC Test', $params['teamName']);
		$this->assertSame([], $params['history']);
		$this->assertSame(0, $params['noOfTeamsInLeague']);
		$this->assertSame(2, $params['leagueid']);
	}
}
