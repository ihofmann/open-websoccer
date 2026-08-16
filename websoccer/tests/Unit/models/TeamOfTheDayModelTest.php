<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TeamOfTheDayModel.
 */
final class TeamOfTheDayModelTest extends TestCaseBase {
	private function dbMock(int $aggValue = 0): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
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
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new TeamOfTheDayModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsDefaultsWhenNoData(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new TeamOfTheDayModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['leagues']);
		$this->assertNull($params['leagueId']);
		$this->assertSame([], $params['seasons']);
		$this->assertSame(0, $params['maxMatchDay']);
		$this->assertFalse($params['openMatchesExist']);
		$this->assertSame([], $params['players']);
	}
}
