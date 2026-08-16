<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CupGroupDetailsModel.
 */
final class CupGroupDetailsModelTest extends TestCaseBase {
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

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->websoccerWithRequest([], ['db_prefix' => 'ws']);
		$model = new CupGroupDetailsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsMatchesAndGroupteams(): void {
		$db = $this->dbMock(['_cup_round AS R' => [['cup_name' => 'MyCup', 'round_name' => 'Round1']]]);
		$ws = $this->websoccerWithRequest(['roundid' => 3, 'group' => 'A'], ['db_prefix' => 'ws']);
		$model = new CupGroupDetailsModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('matches', $params);
		$this->assertArrayHasKey('groupteams', $params);
		$this->assertSame([], $params['matches']);
		$this->assertSame([], $params['groupteams']);
	}

	public function testGetTemplateParametersWithEmptyRoundData(): void {
		$ws = $this->websoccerWithRequest(['roundid' => 3, 'group' => 'A'], ['db_prefix' => 'ws']);
		$model = new CupGroupDetailsModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('matches', $params);
		$this->assertArrayHasKey('groupteams', $params);
	}
}
