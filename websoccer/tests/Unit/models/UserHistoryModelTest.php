<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserHistoryModel.
 */
final class UserHistoryModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsFalseWhenNoUserId(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function () { return null; });
		$model = new UserHistoryModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenUserIdProvided(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserHistoryModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyLeaguesAndCupsWhenNoData(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserHistoryModel($this->mockDb(), $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['leagues']);
		$this->assertSame([], $params['cups']);
	}

	public function testGetTemplateParametersGroupsLeagueAchievements(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['team_id' => 10, 'team_name' => 'FC Test', 'league_name' => 'Premier',
				'season_name' => '2024', 'season_rank' => 1, 'achievement_id' => 1,
				'achievement_date' => 100, 'cup_name' => '', 'cup_round_name' => ''],
		]));
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserHistoryModel($db, $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('Premier', $params['leagues']);
		$this->assertSame([], $params['cups']);
	}

	public function testGetTemplateParametersGroupsCupAchievements(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['team_id' => 10, 'team_name' => 'FC Test', 'league_name' => '',
				'season_name' => '2024', 'season_rank' => 0, 'achievement_id' => 2,
				'achievement_date' => 200, 'cup_name' => 'FA Cup', 'cup_round_name' => 'Final'],
		]));
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserHistoryModel($db, $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['leagues']);
		$this->assertArrayHasKey('FA Cup', $params['cups']);
	}
}
