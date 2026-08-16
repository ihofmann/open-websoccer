<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PremiumAccountModel.
 */
final class PremiumAccountModelTest extends TestCaseBase {
	private function dbMock(int $hits = 0, array $rows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($hits, $rows) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $hits]]);
			}
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$model = new PremiumAccountModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyStatementsWhenNone(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new PremiumAccountModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['statements']);
		$this->assertSame([], $params['payments']);
		$this->assertInstanceOf(Paginator::class, $params['paginator']);
	}
}
