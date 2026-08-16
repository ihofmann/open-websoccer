<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchRequestsModel.
 */
final class YouthMatchRequestsModelTest extends TestCaseBase {
	private function dbMock(int $count = 0, array $requests = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($count, $requests) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $count]]);
			}
			return $this->dbResult($requests);
		});
		return $db;
	}

	private function ws(array $config): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewReturnsTrueWhenBothEnabled(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequests_enabled' => 1,
			'db_prefix' => 'ws', 'entries_per_page' => 10,
			'youth_matchrequest_accept_hours_in_advance' => 2,
		]);
		$model = new YouthMatchRequestsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenMatchRequestsDisabled(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequests_enabled' => 0,
			'db_prefix' => 'ws', 'entries_per_page' => 10,
			'youth_matchrequest_accept_hours_in_advance' => 2,
		]);
		$model = new YouthMatchRequestsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyRequestsWhenNone(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequests_enabled' => 1,
			'db_prefix' => 'ws', 'entries_per_page' => 10,
			'youth_matchrequest_accept_hours_in_advance' => 2,
		]);
		$model = new YouthMatchRequestsModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('requests', $params);
		$this->assertSame([], $params['requests']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}

	public function testGetTemplateParametersReturnsRequestsFromDb(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequests_enabled' => 1,
			'db_prefix' => 'ws', 'entries_per_page' => 10,
			'youth_matchrequest_accept_hours_in_advance' => 2,
		]);
		$requests = [['request_id' => 1, 'team_name' => 'FC Test', 'matchdate' => time() + 3600,
			'user_picture' => '', 'user_email' => '']];
		$model = new YouthMatchRequestsModel($this->dbMock(1, $requests), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['requests']);
	}
}
