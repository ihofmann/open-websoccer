<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchFormationModel.
 */
final class YouthMatchFormationModelTest extends TestCaseBase {
	private function matchRow(array $overrides = []): array {
		$base = [
			'id' => 1,
			'home_team_id' => 10,
			'guest_team_id' => 20,
			'matchdate' => time() + 3600,
			'simulated' => 0,
			'home_team_name' => 'Home',
			'guest_team_name' => 'Guest',
			'home_s1_out' => '', 'home_s1_in' => '', 'home_s1_minute' => '', 'home_s1_condition' => '', 'home_s1_position' => '',
			'home_s2_out' => '', 'home_s2_in' => '', 'home_s2_minute' => '', 'home_s2_condition' => '', 'home_s2_position' => '',
			'home_s3_out' => '', 'home_s3_in' => '', 'home_s3_minute' => '', 'home_s3_condition' => '', 'home_s3_position' => '',
			'guest_s1_out' => '', 'guest_s1_in' => '', 'guest_s1_minute' => '', 'guest_s1_condition' => '', 'guest_s1_position' => '',
			'guest_s2_out' => '', 'guest_s2_in' => '', 'guest_s2_minute' => '', 'guest_s2_condition' => '', 'guest_s2_position' => '',
			'guest_s3_out' => '', 'guest_s3_in' => '', 'guest_s3_minute' => '', 'guest_s3_condition' => '', 'guest_s3_position' => '',
		];
		return array_merge($base, $overrides);
	}

	private function ws(array $config, \User $user, $requestCb = null): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb ?? function () { return null; });
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	/** Stubs three consecutive querySelect results: matchinfo, team players, saved formation. */
	private function dbForMatch(array $match, array $players = [], array $formationPlayers = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$match]),
			$this->dbResult($players),
			$this->dbResult($formationPlayers)
		);
		return $db;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['db_prefix' => 'ws'], $user);
		$model = new YouthMatchFormationModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsFormationForHomeTeam(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->ws(['db_prefix' => 'ws'], $user, function ($name) { return ($name === 'matchid') ? 1 : null; });
		$model = new YouthMatchFormationModel($this->dbForMatch($this->matchRow()), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(1, $params['matchinfo']['id']);
		$this->assertSame([], $params['players']);
		$this->assertTrue($params['youthFormation']);
		$this->assertArrayHasKey('setup', $params);
		$this->assertSame(4, $params['setup']['defense']);
	}

	public function testGetTemplateParametersThrowsWhenMatchDoesNotInvolveClub(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(99);
		$ws = $this->ws(['db_prefix' => 'ws'], $user, function ($name) { return ($name === 'matchid') ? 1 : null; });
		$model = new YouthMatchFormationModel($this->dbForMatch($this->matchRow(['home_team_id' => 50, 'guest_team_id' => 60])), $this->mockI18n(), $ws);
		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersThrowsWhenMatchExpired(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->ws(['db_prefix' => 'ws'], $user, function ($name) { return ($name === 'matchid') ? 1 : null; });
		$model = new YouthMatchFormationModel($this->dbForMatch($this->matchRow(['matchdate' => time() - 3600])), $this->mockI18n(), $ws);
		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}
}
