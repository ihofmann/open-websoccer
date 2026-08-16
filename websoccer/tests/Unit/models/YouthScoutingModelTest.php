<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthScoutingModel.
 */
final class YouthScoutingModelTest extends TestCaseBase {
	private function dbMock(int $lastExecution = 0, array $scouts = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($lastExecution, $scouts) {
			if (is_string($columns) && stripos($columns, 'scouting_last_execution') !== false) {
				return $this->dbResult([['scouting_last_execution' => $lastExecution]]);
			}
			return $this->dbResult($scouts);
		});
		return $db;
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

	public function testRenderViewReturnsTrueWhenBothEnabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['youth_enabled' => 1, 'youth_scouting_enabled' => 1,
			'db_prefix' => 'ws', 'youth_scouting_break_hours' => 0], $user);
		$model = new YouthScoutingModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenScoutingDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['youth_enabled' => 1, 'youth_scouting_enabled' => 0,
			'db_prefix' => 'ws', 'youth_scouting_break_hours' => 0], $user);
		$model = new YouthScoutingModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsScoutingPossibleWhenBreakElapsed(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$now = time();
		$ws = $this->ws(['youth_enabled' => 1, 'youth_scouting_enabled' => 1,
			'db_prefix' => 'ws', 'youth_scouting_break_hours' => 0], $user);
		// lastExecution = 0, break = 0 → nextPossible = 0 <= now → possible
		$model = new YouthScoutingModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertTrue($params['scoutingPossible']);
		$this->assertSame(0, $params['lastExecutionTimestamp']);
		$this->assertSame([], $params['scouts']);
		$this->assertSame([], $params['countries']);
	}

	public function testGetTemplateParametersReturnsScoutingNotPossibleWhenWithinBreak(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$now = time();
		$ws = $this->ws(['youth_enabled' => 1, 'youth_scouting_enabled' => 1,
			'db_prefix' => 'ws', 'youth_scouting_break_hours' => 24], $user);
		// lastExecution = now, break = 24h → nextPossible = now + 24h > now → not possible
		$model = new YouthScoutingModel($this->dbMock($now), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertFalse($params['scoutingPossible']);
		$this->assertSame($now, $params['lastExecutionTimestamp']);
		$this->assertSame($now + 24 * 3600, $params['nextPossibleExecutionTimestamp']);
		$this->assertSame([], $params['scouts']);
	}

	public function testGetTemplateParametersReturnsScoutsWhenPossibleAndNoScoutId(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$scouts = [['id' => 1, 'name' => 'Scout A', 'expertise' => 5]];
		$ws = $this->ws(['youth_enabled' => 1, 'youth_scouting_enabled' => 1,
			'db_prefix' => 'ws', 'youth_scouting_break_hours' => 0], $user);
		$model = new YouthScoutingModel($this->dbMock(0, $scouts), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertTrue($params['scoutingPossible']);
		$this->assertCount(1, $params['scouts']);
	}
}
