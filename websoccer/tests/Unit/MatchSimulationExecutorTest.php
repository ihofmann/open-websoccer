<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for MatchSimulationExecutor.
 *
 * MatchSimulationExecutor::simulateOpenMatches is heavily coupled to many
 * services and the database. These tests focus on the public addPlayers()
 * method and basic integration paths with mocked collaborators.
 */
final class MatchSimulationExecutorTest extends TestCaseBase {
	private function makeMatchinfo(int $matchId = 1, string $type = 'Ligaspiel'): array {
		$info = [
			'match_id' => $matchId,
			'type' => $type,
			'home_id' => 1,
			'guest_id' => 2,
			'minutes' => 0,
			'soldout' => 0,
			'penaltyshooting' => 0,
			'cup_name' => '',
			'cup_roundname' => '',
			'cup_groupname' => '',
			'custom_stadium_id' => 0,
			'player_with_ball' => 0,
			'prev_player_with_ball' => 0,
			'home_goals' => 0,
			'guest_goals' => 0,
			'home_offensive' => 50,
			'home_setup' => '4-4-2',
			'home_noformation' => 0,
			'home_longpasses' => 0,
			'home_counterattacks' => 0,
			'home_morale' => 0,
			'home_freekickplayer' => 0,
			'guest_offensive' => 50,
			'guest_noformation' => 0,
			'guest_setup' => '4-4-2',
			'guest_longpasses' => 0,
			'guest_counterattacks' => 0,
			'guest_morale' => 0,
			'guest_freekickplayer' => 0,
			'home_formation_id' => 1,
			'guest_formation_id' => 1,
			'home_formation_offensive' => 50,
			'home_formation_setup' => '4-4-2',
			'home_formation_longpasses' => 0,
			'home_formation_counterattacks' => 0,
			'home_formation_freekickplayer' => 0,
			'home_name' => 'Home FC',
			'home_nationalteam' => 0,
			'home_interimmanager' => 0,
			'home_captain_id' => 0,
			'guest_nationalteam' => 0,
			'guest_name' => 'Guest Utd',
			'guest_captain_id' => 0,
			'guest_interimmanager' => 0,
			'guest_formation_offensive' => 50,
			'guest_formation_setup' => '4-4-2',
			'guest_formation_longpasses' => 0,
			'guest_formation_counterattacks' => 0,
			'guest_formation_freekickplayer' => 0,
		];

		// Add player and bench columns
		for ($i = 1; $i <= 11; $i++) {
			$info['home_formation_player' . $i] = 0;
			$info['home_formation_player_pos_' . $i] = '';
			$info['guest_formation_player' . $i] = 0;
			$info['guest_formation_player_pos_' . $i] = '';
			if ($i <= 5) {
				$info['home_formation_bench' . $i] = 0;
				$info['guest_formation_bench' . $i] = 0;
			}
		}

		// Add substitution columns
		for ($i = 1; $i <= 3; $i++) {
			$info['home_formation_sub' . $i . '_out'] = 0;
			$info['home_formation_sub' . $i . '_in'] = 0;
			$info['home_formation_sub' . $i . '_minute'] = 0;
			$info['home_formation_sub' . $i . '_condition'] = null;
			$info['home_formation_sub' . $i . '_position'] = null;
			$info['guest_formation_sub' . $i . '_out'] = 0;
			$info['guest_formation_sub' . $i . '_in'] = 0;
			$info['guest_formation_sub' . $i . '_minute'] = 0;
			$info['guest_formation_sub' . $i . '_condition'] = null;
			$info['guest_formation_sub' . $i . '_position'] = null;
			// state loading columns
			$info['home_sub_' . $i . '_out'] = 0;
			$info['home_sub_' . $i . '_in'] = 0;
			$info['home_sub_' . $i . '_minute'] = 0;
			$info['home_sub_' . $i . '_condition'] = null;
			$info['home_sub_' . $i . '_position'] = null;
			$info['guest_sub_' . $i . '_out'] = 0;
			$info['guest_sub_' . $i . '_in'] = 0;
			$info['guest_sub_' . $i . '_minute'] = 0;
			$info['guest_sub_' . $i . '_condition'] = null;
			$info['guest_sub_' . $i . '_position'] = null;
		}

		return $info;
	}

	public function testAddPlayersWithNoPlayersAddsNothing(): void {
		$db = $this->createMock(\DbConnection::class);
		// All player queries return empty (player IDs are 0)
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->method('queryInsert')->willReturn(null);

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'sim_strength_reduction_wrongposition' => 10,
			'sim_strength_reduction_secondary' => 5,
			'sim_createformation_on_invalidsubmission' => FALSE,
		]);

		$team = new SimulationTeam(1, 50);
		$matchinfo = $this->makeMatchinfo();

		MatchSimulationExecutor::addPlayers($ws, $db, $team, $matchinfo, 'home');

		$totalPlayers = count($team->positionsAndPlayers[PLAYER_POSITION_GOALY])
			+ count($team->positionsAndPlayers[PLAYER_POSITION_DEFENCE])
			+ count($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD])
			+ count($team->positionsAndPlayers[PLAYER_POSITION_STRIKER]);
		$this->assertSame(0, $totalPlayers);
	}

	public function testAddPlayersAddsSinglePlayer(): void {
		$playerRow = [
			'team_id' => 1, 'nation' => 'DE',
			'position' => PLAYER_POSITION_STRIKER, 'mainPosition' => 'MS',
			'secondPosition' => '', 'firstName' => 'John', 'lastName' => 'Doe',
			'pseudonym' => '', 'strength' => 85, 'technique' => 75, 'stamina' => 70,
			'freshness' => 90, 'satisfaction' => 80, 'age' => 25, 'matches_played' => 100,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use ($playerRow) {
			// Only return a row for player ID 1; return empty for ID 0 (unfilled slots)
			if ($parameters == 1) {
				return new MockDbResult([$playerRow]);
			}
			return new MockDbResult([]);
		});
		$db->method('queryInsert')->willReturn(null);

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'sim_strength_reduction_wrongposition' => 10,
			'sim_strength_reduction_secondary' => 5,
			'sim_createformation_on_invalidsubmission' => FALSE,
		]);

		$team = new SimulationTeam(1, 50);
		$matchinfo = $this->makeMatchinfo();
		$matchinfo['home_formation_player1'] = 1;
		$matchinfo['home_formation_player_pos_1'] = 'MS';

		MatchSimulationExecutor::addPlayers($ws, $db, $team, $matchinfo, 'home');

		// At least one player should be added (the query returns a row for each call,
		// but only the first player with team_id=1 matches the team check)
		$totalPlayers = count($team->positionsAndPlayers[PLAYER_POSITION_STRIKER]);
		$this->assertGreaterThan(0, $totalPlayers);
	}
}
