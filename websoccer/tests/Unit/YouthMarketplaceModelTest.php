<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMarketplaceModel.
 */
final class YouthMarketplaceModelTest extends TestCaseBase {
	private function dbMock(int $count = 0, array $players = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($count, $players) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $count]]);
			}
			return $this->dbResult($players);
		});
		return $db;
	}

	private function ws(array $config, $requestCb): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewReturnsTrueWhenYouthEnabled(): void {
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], function () { return null; });
		$model = new YouthMarketplaceModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(1, $model->renderView());
	}

	public function testRenderViewReturnsFalseWhenYouthDisabled(): void {
		$ws = $this->ws(['youth_enabled' => 0, 'db_prefix' => 'ws', 'entries_per_page' => 10], function () { return null; });
		$model = new YouthMarketplaceModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyPlayersWhenNone(): void {
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], function () { return null; });
		$model = new YouthMarketplaceModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('players', $params);
		$this->assertSame([], $params['players']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}

	public function testGetTemplateParametersReturnsPlayersFromDb(): void {
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], function () { return null; });
		$players = [['player_id' => 1, 'firstname' => 'John', 'lastname' => 'Doe',
			'nation' => '', 'user_picture' => '', 'user_email' => '']];
		$model = new YouthMarketplaceModel($this->dbMock(1, $players), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['players']);
	}
}
