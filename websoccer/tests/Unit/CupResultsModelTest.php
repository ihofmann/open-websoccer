<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CupResultsModel.
 */
final class CupResultsModelTest extends TestCaseBase {
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

	private function websoccerWithUser(\User $user, array $config = [], array $request = []): \WebSoccer {
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
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new CupResultsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersStructureForNonGroupRound(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws'], ['cup' => 'MyCup', 'round' => 'Final']);
		$model = new CupResultsModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('matches', $params);
		$this->assertArrayHasKey('round', $params);
		$this->assertArrayHasKey('groups', $params);
		$this->assertArrayHasKey('preSelectedGroup', $params);
		$this->assertSame([], $params['matches']);
		$this->assertSame([], $params['groups']);
		$this->assertSame('', $params['preSelectedGroup']);
	}

	public function testGetTemplateParametersForGroupRoundBuildsGroups(): void {
		$user = $this->makeUser(['id' => 1]);
		$round = [
			'round_id' => 9,
			'is_groupround' => 1,
			'is_finalround' => 0,
			'cup_logo' => '',
			'firstround_date' => 0,
			'secondround_date' => 0,
			'prev_round_winners' => null,
			'prev_round_loosers' => null,
		];
		$groupRows = [
			['name' => 'A', 'team_id' => 11],
			['name' => 'B', 'team_id' => 12],
		];
		$db = $this->dbMock(['_cup_round AS R' => [$round], '_cup_round_group' => $groupRows]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws'], ['cup' => 'MyCup', 'round' => 'Group']);
		$model = new CupResultsModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(['A' => 'A', 'B' => 'B'], $params['groups']);
		$this->assertSame([], $params['matches']);
	}
}
