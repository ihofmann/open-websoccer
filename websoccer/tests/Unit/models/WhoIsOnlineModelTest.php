<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for WhoIsOnlineModel.
 */
final class WhoIsOnlineModelTest extends TestCaseBase {
	private function dbMock(int $count = 0, array $users = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($count, $users) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $count]]);
			}
			return $this->dbResult($users);
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

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->ws(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$model = new WhoIsOnlineModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyUsersWhenNoneOnline(): void {
		$ws = $this->ws(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$model = new WhoIsOnlineModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('users', $params);
		$this->assertSame([], $params['users']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}

	public function testGetTemplateParametersReturnsUsersWhenCountGreaterThanZero(): void {
		$ws = $this->ws(['db_prefix' => 'ws', 'entries_per_page' => 10]);
		$users = [['id' => 1, 'nick' => 'bob', 'email' => '', 'picture' => '']];
		$model = new WhoIsOnlineModel($this->dbMock(1, $users), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['users']);
	}
}
