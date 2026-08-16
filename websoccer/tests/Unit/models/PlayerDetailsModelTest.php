<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PlayerDetailsModel.
 */
final class PlayerDetailsModelTest extends TestCaseBase {
	private function ws(array $config, $requestValue = null): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback(function() use ($requestValue) { return $requestValue; });
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	private function dbWithCachedPlayer(array $cached): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			return $this->dbResult([]);
		});
		return $db;
	}

	private function config(): array {
		return ['db_prefix' => 'ws', 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => 0];
	}

	public function testRenderViewReturnsTrue(): void {
		$model = new PlayerDetailsModel($this->dbWithCachedPlayer([]), $this->mockI18n(), $this->ws($this->config()));
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenIdInvalid(): void {
		$ws = $this->ws($this->config(), 0);
		$model = new PlayerDetailsModel($this->dbWithCachedPlayer([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'not found']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersThrowsWhenPlayerNotFound(): void {
		$ws = $this->ws($this->config(), 5);
		$model = new PlayerDetailsModel($this->dbWithCachedPlayer([]), $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'not found']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsPlayer(): void {
		$ws = $this->ws($this->config(), 5);
		$row = [
			'player_id' => 5, 'player_position' => 'Torwart', 'player_nationality' => '',
			'player_marketvalue' => 1000, 'matches_info' => '0;0'
		];
		$model = new PlayerDetailsModel($this->dbWithCachedPlayer([$row]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(5, $params['player']['player_id']);
		$this->assertSame('goaly', $params['player']['player_position']);
	}
}
