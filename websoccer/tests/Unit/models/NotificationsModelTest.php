<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NotificationsModel.
 */
final class NotificationsModelTest extends TestCaseBase {
	private function dbMock(): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => 0]]);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'notifications_max' => 10]);
		$model = new NotificationsModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyNotificationsForGuest(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'notifications_max' => 10]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NotificationsModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('notifications', $params);
		$this->assertSame([], $params['notifications']);
	}
}
