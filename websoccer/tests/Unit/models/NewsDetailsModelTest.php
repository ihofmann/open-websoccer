<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NewsDetailsModel.
 */
final class NewsDetailsModelTest extends TestCaseBase {
	private function dbWithRows(array $rows): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new NewsDetailsModel($this->dbWithRows([]), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenNewsItemNotFound(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getRequestParameter')->willReturn(99);
		$model = new NewsDetailsModel($this->dbWithRows([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'not found']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsArticleAndRelatedLinks(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getRequestParameter')->willReturn(7);
		$row = [
			'id' => 7, 'titel' => 'My News', 'nachricht' => 'Hello', 'datum' => 1000,
			'c_br' => 0, 'c_links' => 0, 'author_name' => 'Admin',
			'linktext1' => 'Link1', 'linkurl1' => 'http://example.com',
			'linktext2' => 'Link2', 'linkurl2' => 'http://example.com',
			'linktext3' => 'Link3', 'linkurl3' => 'https://example.org'
		];
		$model = new NewsDetailsModel($this->dbWithRows([$row]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(7, $params['article']['id']);
		$this->assertSame('My News', $params['article']['title']);
		$this->assertSame('Admin', $params['article']['author_name']);
		$this->assertSame([
			['url' => 'http://example.com', 'label' => 'Link1'],
			['url' => 'http://example.com', 'label' => 'Link2'],
			['url' => 'https://example.org', 'label' => 'Link3']
		], $params['relatedLinks']);
	}
}
