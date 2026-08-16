<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TodaysMatchesModel.
 */
final class TodaysMatchesModelTest extends TestCaseBase {
	private function dbMock(int $hits = 0, array $rows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($hits, $rows) {
			$cols = is_string($columns) ? $columns : '';
			if (preg_match('/(COUNT|MAX|MIN|SUM|AVG)\s*\(/i', $cols)) {
				$alias = 'hits';
				if (preg_match('/AS\s+`?(\w+)`?/i', $cols, $m)) $alias = $m[1];
				return $this->dbResult([[$alias => $hits]]);
			}
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$model = new TodaysMatchesModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyMatchesAndNullPaginatorWhenNoMatches(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$model = new TodaysMatchesModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['matches']);
		$this->assertNull($params['paginator']);
	}
}
