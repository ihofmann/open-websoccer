<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for ProfileBlockModel.
 */
final class ProfileBlockModelTest extends TestCaseBase {
	private function dbMock(array $rows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => 0]]);
			}
			if (is_string($columns) && stripos($columns, 'fanbeliebtheit') !== false) {
				return $this->dbResult($rows);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsFalseForGuest(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['username' => '']));
		$model = new ProfileBlockModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueForLoggedInUser(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1, 'username' => 'foo']));
		$model = new ProfileBlockModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsProfileAndCounts(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1, 'username' => 'foo']));
		$model = new ProfileBlockModel($this->dbMock([['user_popularity' => 5, 'user_highscore' => 9001]]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(5, $params['profile']['user_popularity']);
		$this->assertNull($params['userteam']);
		$this->assertSame(0, $params['unseenMessages']);
		$this->assertSame(0, $params['unseenNotifications']);
	}
}
