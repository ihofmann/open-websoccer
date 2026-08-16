<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LentPlayersModel.
 */
final class LentPlayersModelTest extends TestCaseBase {
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

	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'players_aging' => 'birthday']);
		$model = new LentPlayersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyLentPlayersWhenNoData(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'players_aging' => 'birthday']);
		$model = new LentPlayersModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('lentplayers', $params);
		$this->assertSame([], $params['lentplayers']);
	}

	public function testGetTemplateParametersReturnsLentPlayersWithConvertedPosition(): void {
		$row = [
			'id' => 5, 'firstname' => 'John', 'lastname' => 'Doe', 'pseudonym' => '',
			'position' => 'Torwart', 'position_main' => 'T', 'position_second' => '',
			'player_nationality' => 'England', 'lending_matches' => 3, 'lending_fee' => 100,
			'team_id' => 20, 'team_name' => 'Other', 'age' => 25,
		];
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'players_aging' => 'birthday']);
		$model = new LentPlayersModel($this->dbMock(['_spieler P' => [$row]]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['lentplayers']);
		$this->assertSame('goaly', $params['lentplayers'][0]['position']);
	}
}
