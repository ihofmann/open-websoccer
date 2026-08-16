<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LeagueSelectionModel.
 */
final class LeagueSelectionModelTest extends TestCaseBase {
	private function dbMock(array $selectMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($selectMap) {
			foreach ($selectMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $this->dbResult($rows);
				}
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	private function websoccerWithRequest(array $request, array $config = []): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback(function ($name) use ($request) {
			return $request[$name] ?? null;
		});
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewReturnsZeroWhenNoCountry(): void {
		$ws = $this->websoccerWithRequest([], ['db_prefix' => 'ws']);
		$model = new LeagueSelectionModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testRenderViewReturnsNonZeroWhenCountryProvided(): void {
		$ws = $this->websoccerWithRequest(['country' => 'England'], ['db_prefix' => 'ws']);
		$model = new LeagueSelectionModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertGreaterThan(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsLeaguesFromDb(): void {
		$rows = [['id' => 1, 'name' => 'Premier'], ['id' => 2, 'name' => 'Championship']];
		$ws = $this->websoccerWithRequest(['country' => 'England'], ['db_prefix' => 'ws']);
		$model = new LeagueSelectionModel($this->dbMock(['_liga' => $rows]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame($rows, $params['leagues']);
	}

	public function testGetTemplateParametersReturnsEmptyWhenNoLeagues(): void {
		$ws = $this->websoccerWithRequest(['country' => 'England'], ['db_prefix' => 'ws']);
		$model = new LeagueSelectionModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['leagues']);
	}
}
