<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchReportModel.
 */
final class YouthMatchReportModelTest extends TestCaseBase {
	private function matchRow(array $overrides = []): array {
		return array_merge([
			'id' => 1,
			'home_team_id' => 10,
			'guest_team_id' => 20,
			'home_team_name' => 'Home',
			'guest_team_name' => 'Guest',
		], $overrides);
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
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthMatchReportModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(1, $model->renderView());
	}

	public function testRenderViewReturnsFalseWhenYouthDisabled(): void {
		$ws = $this->ws(['youth_enabled' => 0, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthMatchReportModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyPlayersAndStatsWhenNoData(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$this->matchRow()]),
			$this->dbResult([]),
			$this->dbResult([])
		);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function ($name) { return ($name === 'id') ? 1 : null; });
		$model = new YouthMatchReportModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(1, $params['match']['id']);
		$this->assertSame([], $params['players']);
		$this->assertSame([], $params['statistics']);
		$this->assertSame([], $params['reportMessages']);
	}

	public function testGetTemplateParametersComputesStatisticsForBothTeams(): void {
		$homePlayer = ['team_id' => 10, 'strength' => 80, 'ballcontacts' => 50, 'wontackles' => 5,
			'shoots' => 3, 'passes_successed' => 20, 'passes_failed' => 2, 'assists' => 1, 'playernumber' => 1];
		$guestPlayer = ['team_id' => 20, 'strength' => 60, 'ballcontacts' => 50, 'wontackles' => 3,
			'shoots' => 2, 'passes_successed' => 15, 'passes_failed' => 5, 'assists' => 0, 'playernumber' => 1];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$this->matchRow()]),
			$this->dbResult([$homePlayer, $guestPlayer]),
			$this->dbResult([])
		);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function ($name) { return ($name === 'id') ? 1 : null; });
		$model = new YouthMatchReportModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['players']['home']);
		$this->assertCount(1, $params['players']['guest']);
		$this->assertSame(3, $params['statistics']['home']['losttackles']);
		$this->assertSame(5, $params['statistics']['guest']['losttackles']);
		$this->assertEquals(80, $params['statistics']['home']['avg_strength']);
		$this->assertEquals(50, $params['statistics']['home']['ballpossession']);
	}
}
