<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TopNewsListModel.
 */
final class TopNewsListModelTest extends TestCaseBase {
	private function dbMock(array $rows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new TopNewsListModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyTopNewsWhenNone(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new TopNewsListModel($this->dbMock([]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['topnews']);
	}

	public function testGetTemplateParametersReturnsTopNewsWhenExist(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getFormattedDate')->willReturn('2024-01-01');
		$rows = [['id' => 1, 'titel' => 'A', 'datum' => 1000], ['id' => 2, 'titel' => 'B', 'datum' => 2000]];
		$model = new TopNewsListModel($this->dbMock($rows), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['topnews']);
		$this->assertSame(1, $params['topnews'][0]['id']);
		$this->assertSame('2024-01-01', $params['topnews'][0]['date']);
	}
}
