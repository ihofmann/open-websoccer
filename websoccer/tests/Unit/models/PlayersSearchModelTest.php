<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PlayersSearchModel.
 */
final class PlayersSearchModelTest extends TestCaseBase {
	private function ws(array $config, ?callable $requestCb = null): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb ?? function() { return null; });
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

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

	private function config(): array {
		return ['db_prefix' => 'ws', 'players_aging' => 'birthday', 'entries_per_page' => 10];
	}

	public function testRenderViewReturnsFalseWhenNoFilterProvided(): void {
		$model = new PlayersSearchModel($this->dbMock(), $this->mockI18n(), $this->ws($this->config()));
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenFilterProvided(): void {
		$ws = $this->ws($this->config(), function($name) { return ($name === 'fname') ? 'John' : null; });
		$model = new PlayersSearchModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyPlayersWhenNoMatches(): void {
		$ws = $this->ws($this->config(), function($name) { return ($name === 'fname') ? 'John' : null; });
		$model = new PlayersSearchModel($this->dbMock(0), $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertSame(0, $params['playersCount']);
		$this->assertSame([], $params['players']);
		$this->assertInstanceOf(Paginator::class, $params['paginator']);
	}
}
