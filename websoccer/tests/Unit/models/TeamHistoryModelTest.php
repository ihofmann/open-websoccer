<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TeamHistoryModel.
 */
final class TeamHistoryModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsFalseWhenNoTeamId(): void {
		$model = new TeamHistoryModel($this->mockDb(), $this->mockI18n(), $this->ws(['db_prefix' => 'ws']));
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenTeamIdProvided(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$model = new TeamHistoryModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyLeaguesAndCupsWhenNoAchievements(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$model = new TeamHistoryModel($this->mockDb(), $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['leagues']);
		$this->assertSame([], $params['cups']);
	}

	public function testGetTemplateParametersGroupsLeagueAchievements(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], 5);
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$rows = [['league_name' => 'Premier', 'season_rank' => 1, 'cup_name' => '', 'achievement_id' => 1]];
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			return $this->dbResult($rows);
		});
		$db->method('queryDelete');
		$model = new TeamHistoryModel($db, $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('Premier', $params['leagues']);
		$this->assertSame([], $params['cups']);
	}
}
