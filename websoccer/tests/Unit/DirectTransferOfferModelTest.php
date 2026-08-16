<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DirectTransferOfferModel.
 */
final class DirectTransferOfferModelTest extends TestCaseBase {
	private function dbMock(array $selectMap = [], array $cachedMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($selectMap) {
			foreach ($selectMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $this->dbResult($rows);
				}
			}
			return $this->dbResult([]);
		});
		$db->method('queryCachedSelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($cachedMap) {
			foreach ($cachedMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $rows;
				}
			}
			return [];
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

	public function testConstructorThrowsWhenNoPlayerIdProvided(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'transferoffers_enabled' => TRUE]);

		$this->expectException(\Exception::class);
		new DirectTransferOfferModel($this->mockDb(), $this->mockI18n(), $ws);
	}

	public function testRenderViewReturnsFalseWhenFeatureDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$player = ['player_id' => 5, 'player_unsellable' => 0, 'team_user_id' => 2,
			'player_transfermarket' => 0, 'lending_owner_id' => 0, 'player_marketvalue' => 1000, 'player_position' => 'Torwart', 'player_nationality' => 'Deutschland', 'matches_info' => '0;0'];
		$db = $this->dbMock([], ['_spieler AS P' => [$player]]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'transferoffers_enabled' => FALSE, 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => FALSE], ['id' => 5]);

		$model = new DirectTransferOfferModel($db, $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueForSellablePlayerOfOtherManager(): void {
		$user = $this->makeUser(['id' => 1]);
		$player = ['player_id' => 5, 'player_unsellable' => 0, 'team_user_id' => 2,
			'player_transfermarket' => 0, 'lending_owner_id' => 0, 'player_marketvalue' => 1000, 'player_position' => 'Torwart', 'player_nationality' => 'Deutschland', 'matches_info' => '0;0'];
		$db = $this->dbMock([], ['_spieler AS P' => [$player]]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'transferoffers_enabled' => TRUE, 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => FALSE], ['id' => 5]);

		$model = new DirectTransferOfferModel($db, $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenPlayerIsOnTransfermarket(): void {
		$user = $this->makeUser(['id' => 1]);
		$player = ['player_id' => 5, 'player_unsellable' => 0, 'team_user_id' => 2,
			'player_transfermarket' => 1, 'lending_owner_id' => 0, 'player_marketvalue' => 1000, 'player_position' => 'Torwart', 'player_nationality' => 'Deutschland', 'matches_info' => '0;0'];
		$db = $this->dbMock([], ['_spieler AS P' => [$player]]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'transferoffers_enabled' => TRUE, 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => FALSE], ['id' => 5]);

		$model = new DirectTransferOfferModel($db, $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsPlayersAndPlayer(): void {
		$user = $this->makeUser(['id' => 1]);
		$player = ['player_id' => 5, 'player_unsellable' => 0, 'team_user_id' => 2,
			'player_transfermarket' => 0, 'lending_owner_id' => 0, 'player_marketvalue' => 1000, 'player_position' => 'Torwart', 'player_nationality' => 'Deutschland', 'matches_info' => '0;0'];
		$db = $this->dbMock([], ['_spieler AS P' => [$player]]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'transferoffers_enabled' => TRUE, 'players_aging' => 'birthday', 'transfermarket_computed_marketvalue' => FALSE], ['id' => 5]);

		$model = new DirectTransferOfferModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('players', $params);
		$this->assertArrayHasKey('player', $params);
		$this->assertSame([], $params['players']);
		$this->assertSame(5, $params['player']['player_id']);
		$this->assertSame(2, $params['player']['team_user_id']);
	}
}
