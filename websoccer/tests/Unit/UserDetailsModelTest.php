<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserDetailsModel.
 */
final class UserDetailsModelTest extends TestCaseBase {
	/** Returns a user row for the _user table query, empty for all other tables. */
	private function dbMock(array $userRow = []): \DbConnection {
		$rows = $userRow === [] ? [] : [$userRow];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			if ($fromTable === 'ws_user') {
				return $this->dbResult($rows);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	private function ws(array $config, $requestCb, \User $user): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->ws(['db_prefix' => 'ws', 'nationalteams_enabled' => 0], function () { return null; }, $this->makeUser(['id' => 1]));
		$model = new UserDetailsModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserNotFound(): void {
		$ws = $this->ws(['db_prefix' => 'ws', 'nationalteams_enabled' => 0], function ($name) { return ($name === 'id') ? 99 : null; }, $this->makeUser(['id' => 1]));
		$model = new UserDetailsModel($this->dbMock([]), $this->mockI18n(), $ws);
		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsUserTeamsAbsenceBadges(): void {
		$userRow = ['id' => 7, 'nick' => 'foo', 'email' => '', 'picture' => '', 'highscore' => 0,
			'popularity' => 0, 'registration_date' => 100, 'lastonline' => 200, 'history' => '',
			'name' => '', 'place' => '', 'country' => '', 'birthday' => '', 'occupation' => '',
			'interests' => '', 'favorite_club' => '', 'homepage' => '', 'premium_balance' => 0];
		$ws = $this->ws(['db_prefix' => 'ws', 'nationalteams_enabled' => 0], function ($name) { return ($name === 'id') ? 7 : null; }, $this->makeUser(['id' => 1]));
		$model = new UserDetailsModel($this->dbMock($userRow), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(7, $params['user']['id']);
		$this->assertSame([], $params['userteams']);
		$this->assertFalse($params['absence']);
		$this->assertSame([], $params['badges']);
	}

	public function testGetTemplateParametersQueriesNationalTeamWhenEnabled(): void {
		$userRow = ['id' => 7, 'nick' => 'foo', 'email' => '', 'picture' => '', 'highscore' => 0,
			'popularity' => 0, 'registration_date' => 100, 'lastonline' => 200, 'history' => '',
			'name' => '', 'place' => '', 'country' => '', 'birthday' => '', 'occupation' => '',
			'interests' => '', 'favorite_club' => '', 'homepage' => '', 'premium_balance' => 0];
		$ws = $this->ws(['db_prefix' => 'ws', 'nationalteams_enabled' => 1], function ($name) { return ($name === 'id') ? 7 : null; }, $this->makeUser(['id' => 1]));
		$model = new UserDetailsModel($this->dbMock($userRow), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(7, $params['user']['id']);
		$this->assertArrayNotHasKey('nationalteam', $params['user']);
	}

	public function testGetTemplateParametersFallsBackToLoggedInUserIdWhenNoIdParam(): void {
		$userRow = ['id' => 1, 'nick' => 'me', 'email' => '', 'picture' => '', 'highscore' => 0,
			'popularity' => 0, 'registration_date' => 100, 'lastonline' => 200, 'history' => '',
			'name' => '', 'place' => '', 'country' => '', 'birthday' => '', 'occupation' => '',
			'interests' => '', 'favorite_club' => '', 'homepage' => '', 'premium_balance' => 0];
		$ws = $this->ws(['db_prefix' => 'ws', 'nationalteams_enabled' => 0], function () { return null; }, $this->makeUser(['id' => 1]));
		$model = new UserDetailsModel($this->dbMock($userRow), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(1, $params['user']['id']);
	}
}
