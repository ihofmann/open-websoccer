<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NewsListModel.
 */
final class NewsListModelTest extends TestCaseBase {
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
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new NewsListModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyArticlesWhenNoNews(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new NewsListModel($this->dbMock(0, []), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['articles']);
		$this->assertInstanceOf(Paginator::class, $params['paginator']);
	}

	public function testGetTemplateParametersReturnsArticlesWhenNewsExist(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$rows = [
			['id' => 1, 'titel' => 'A', 'datum' => 1000, 'nachricht' => 'short'],
			['id' => 2, 'titel' => 'B', 'datum' => 2000, 'nachricht' => 'short']
		];
		$model = new NewsListModel($this->dbMock(2, $rows), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['articles']);
		$this->assertSame(1, $params['articles'][0]['id']);
		$this->assertSame('short', $params['articles'][0]['teaser']);
	}
}
