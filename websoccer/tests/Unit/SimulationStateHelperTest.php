<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SimulationStateHelper.
 */
final class SimulationStateHelperTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, PLAYER_POSITION_DEFENCE, 'IV', 3.0, 25, 80, 70, 60, 90, 85);
		$p->name = 'Player' . $id;
		return $p;
	}

	public function testCreateSimulationRecordCallsQueryInsert(): void {
		$db = $this->createMock(\DbConnection::class);
		$insertedColumns = null;
		$insertedTable = null;
		$db->method('queryInsert')->willReturnCallback(function ($columns, $table) use (&$insertedColumns, &$insertedTable) {
			$insertedColumns = $columns;
			$insertedTable = $table;
		});
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);

		$team = $this->makeTeam(1);
		$player = $this->makePlayer(10, $team);

		SimulationStateHelper::createSimulationRecord($ws, $db, 5, $player);

		$this->assertSame('ws_spiel_berechnung', $insertedTable);
		$this->assertSame(5, $insertedColumns['spiel_id']);
		$this->assertSame(10, $insertedColumns['spieler_id']);
		$this->assertSame(1, $insertedColumns['team_id']);
		$this->assertSame('Player10', $insertedColumns['name']);
		$this->assertSame('1', $insertedColumns['feld']);
	}

	public function testCreateSimulationRecordOnBenchSetsFieldArea(): void {
		$db = $this->createMock(\DbConnection::class);
		$insertedColumns = null;
		$db->method('queryInsert')->willReturnCallback(function ($columns) use (&$insertedColumns) {
			$insertedColumns = $columns;
		});
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);

		$team = $this->makeTeam(1);
		$player = $this->makePlayer(10, $team);

		SimulationStateHelper::createSimulationRecord($ws, $db, 5, $player, TRUE);

		$this->assertSame('Ersatzbank', $insertedColumns['feld']);
	}

	public function testUpdateStateCallsQueryUpdate(): void {
		$db = $this->createMock(\DbConnection::class);
		$updateCount = 0;
		$db->method('queryUpdate')->willReturnCallback(function () use (&$updateCount) {
			$updateCount++;
		});
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$home->substitutions = [];
		$guest->substitutions = [];
		$p1 = $this->makePlayer(1, $home);
		$p2 = $this->makePlayer(2, $guest);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p1;
		$guest->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p2;
		$match = new SimulationMatch(1, $home, $guest, 45);

		SimulationStateHelper::updateState($ws, $db, $match);
		// At minimum: 1 match update + 2 player updates
		$this->assertGreaterThanOrEqual(3, $updateCount);
	}

	public function testLoadMatchStateBuildsMatchModel(): void {
		$matchinfo = [
			'match_id' => 1,
			'type' => 'Ligaspiel',
			'home_id' => 10,
			'guest_id' => 20,
			'minutes' => 45,
			'soldout' => 1,
			'penaltyshooting' => 0,
			'cup_name' => '',
			'cup_roundname' => '',
			'cup_groupname' => '',
			'custom_stadium_id' => 0,
			'player_with_ball' => 0,
			'prev_player_with_ball' => 0,
			'home_goals' => 2,
			'guest_goals' => 1,
			'home_offensive' => 60,
			'home_nationalteam' => 0,
			'home_interimmanager' => 0,
			'home_noformation' => 0,
			'home_setup' => '4-4-2',
			'home_name' => 'Home FC',
			'home_longpasses' => 0,
			'home_counterattacks' => 0,
			'home_morale' => 50,
			'home_freekickplayer' => 0,
			'guest_offensive' => 50,
			'guest_nationalteam' => 0,
			'guest_interimmanager' => 0,
			'guest_noformation' => 0,
			'guest_setup' => '4-3-3',
			'guest_name' => 'Guest Utd',
			'guest_longpasses' => 0,
			'guest_counterattacks' => 0,
			'guest_morale' => 30,
			'guest_freekickplayer' => 0,
		];
		// Add substitution columns
		for ($i = 1; $i <= 3; $i++) {
			$matchinfo['home_sub_' . $i . '_out'] = 0;
			$matchinfo['home_sub_' . $i . '_in'] = 0;
			$matchinfo['home_sub_' . $i . '_minute'] = 0;
			$matchinfo['home_sub_' . $i . '_condition'] = null;
			$matchinfo['home_sub_' . $i . '_position'] = null;
			$matchinfo['guest_sub_' . $i . '_out'] = 0;
			$matchinfo['guest_sub_' . $i . '_in'] = 0;
			$matchinfo['guest_sub_' . $i . '_minute'] = 0;
			$matchinfo['guest_sub_' . $i . '_condition'] = null;
			$matchinfo['guest_sub_' . $i . '_position'] = null;
		}

		$playerRows = [
			[
				'player_id' => 1, 'name' => 'Home Keeper', 'mark' => 2.5,
				'minutes_played' => 45, 'yellow_cards' => 0, 'red_cards' => 0,
				'injured' => 0, 'blocked' => 0, 'goals' => 0, 'field_area' => '1',
				'position' => PLAYER_POSITION_GOALY, 'main_position' => 'T', 'age' => 28,
				'strength' => 85, 'strength_tech' => 60, 'strength_stamina' => 80,
				'strength_freshness' => 90, 'strength_satisfaction' => 80,
				'ballcontacts' => 5, 'wontackles' => 0, 'losttackles' => 0,
				'shoots' => 0, 'passes_successed' => 10, 'passes_failed' => 2, 'assists' => 0,
			],
			[
				'player_id' => 2, 'name' => 'Guest Striker', 'mark' => 1.5,
				'minutes_played' => 45, 'yellow_cards' => 1, 'red_cards' => 0,
				'injured' => 0, 'blocked' => 0, 'goals' => 1, 'field_area' => '1',
				'position' => PLAYER_POSITION_STRIKER, 'main_position' => 'MS', 'age' => 24,
				'strength' => 82, 'strength_tech' => 75, 'strength_stamina' => 70,
				'strength_freshness' => 85, 'strength_satisfaction' => 90,
				'ballcontacts' => 15, 'wontackles' => 3, 'losttackles' => 1,
				'shoots' => 4, 'passes_successed' => 8, 'passes_failed' => 3, 'assists' => 0,
			],
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult($playerRows));
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);

		$match = SimulationStateHelper::loadMatchState($ws, $db, $matchinfo);

		$this->assertSame(1, $match->id);
		$this->assertSame('Ligaspiel', $match->type);
		$this->assertSame(45, $match->minute);
		$this->assertSame(2, $match->homeTeam->getGoals());
		$this->assertSame(1, $match->guestTeam->getGoals());
		$this->assertSame('Home FC', $match->homeTeam->name);
		$this->assertSame('Guest Utd', $match->guestTeam->name);
		$this->assertEquals(1, $match->isSoldOut);
	}
}
