<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DirectTransferOfferController.
 */
final class DirectTransferOfferControllerTest extends TestCaseBase {
	private function makeDb(array $cachedRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($cachedRowsByTable) {
				foreach ($cachedRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $rows;
					}
				}
				return [];
			}
		);
		return $db;
	}

	private function makeWebsoccer(array $config, $requestParamId = 9): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn($requestParamId);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	private function makeUserWithClub(?int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		if ($clubId !== null) {
			$user->setClubId($clubId);
		}
		return $user;
	}

	private function baseConfig(): array {
		return ['transferoffers_enabled' => true, 'db_prefix' => 'ws',
			'players_aging' => 'age', 'transfermarket_computed_marketvalue' => false];
	}

	private function playerRow(array $override = []): array {
		return array_merge([
			'player_id' => 9, 'team_id' => 2, 'team_name' => 'Other', 'team_user_id' => 2,
			'player_unsellable' => 0, 'player_transfermarket' => 0,
			'player_position' => 'Torwart', 'player_nationality' => 'Deutschland',
			'matches_info' => '0;0', 'player_marketvalue' => 1000,
		], $override);
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->makeWebsoccer(['transferoffers_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferOfferController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['amount' => 100, 'comment' => '', 'exchangeplayer1' => 0, 'exchangeplayer2' => 0]));
	}

	public function testExecuteActionThrowsWhenUserHasNoTeam(): void {
		$ws = $this->makeWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(null)); // no club

		$controller = new DirectTransferOfferController(
			$this->mockI18n(['feature_requires_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['amount' => 100, 'comment' => '', 'exchangeplayer1' => 0, 'exchangeplayer2' => 0]);
	}

	public function testExecuteActionThrowsWhenPlayerHasNoManager(): void {
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_user_id' => 0])]]);
		$ws = $this->makeWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferOfferController(
			$this->mockI18n(['transferoffer_err_nomanager' => 'no manager']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('no manager');
		$controller->executeAction(['amount' => 100, 'comment' => '', 'exchangeplayer1' => 0, 'exchangeplayer2' => 0]);
	}

	public function testExecuteActionThrowsWhenPlayerIsOwn(): void {
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_user_id' => 1])]]);
		$ws = $this->makeWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferOfferController(
			$this->mockI18n(['transferoffer_err_ownplayer' => 'own player']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('own player');
		$controller->executeAction(['amount' => 100, 'comment' => '', 'exchangeplayer1' => 0, 'exchangeplayer2' => 0]);
	}

	public function testExecuteActionThrowsWhenPlayerIsUnsellable(): void {
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_user_id' => 2, 'player_unsellable' => 1])]]);
		$ws = $this->makeWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferOfferController(
			$this->mockI18n(['transferoffer_err_unsellable' => 'unsellable']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('unsellable');
		$controller->executeAction(['amount' => 100, 'comment' => '', 'exchangeplayer1' => 0, 'exchangeplayer2' => 0]);
	}
}
