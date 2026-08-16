<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SimulationTeam.
 */
final class SimulationTeamTest extends TestCaseBase {
	private function makePlayer(int $id, string $position, string $mainPosition = 'IV', ?SimulationTeam $team = null): SimulationPlayer {
		if ($team === null) {
			$team = new SimulationTeam(1, 50);
		}
		return new SimulationPlayer($id, $team, $position, $mainPosition,
			3.0, 25, 80, 70, 60, 90, 85);
	}

	public function testConstructorSetsIdAndOffensive(): void {
		$team = new SimulationTeam(42, 65);
		$this->assertSame(42, $team->id);
		$this->assertSame(65, $team->offensive);
	}

	public function testConstructorInitializesEmptyPositions(): void {
		$team = new SimulationTeam(1);
		$this->assertSame([], $team->positionsAndPlayers[PLAYER_POSITION_GOALY]);
		$this->assertSame([], $team->positionsAndPlayers[PLAYER_POSITION_DEFENCE]);
		$this->assertSame([], $team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD]);
		$this->assertSame([], $team->positionsAndPlayers[PLAYER_POSITION_STRIKER]);
	}

	public function testConstructorInitializesDefaults(): void {
		$team = new SimulationTeam(1);
		$this->assertSame(0, $team->getGoals());
		$this->assertSame(0, $team->morale);
		$this->assertFalse($team->noFormationSet);
		$this->assertFalse($team->longPasses);
		$this->assertFalse($team->counterattacks);
	}

	public function testSetAndGetGoals(): void {
		$team = new SimulationTeam(1);
		$team->setGoals(3);
		$this->assertSame(3, $team->getGoals());
	}

	public function testRemovePlayerMovesPlayerToRemovedList(): void {
		$team = new SimulationTeam(1);
		$p1 = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 'IV', $team);
		$p2 = $this->makePlayer(2, PLAYER_POSITION_MIDFIELD, 'ZM', $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p1;
		$team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $p2;

		$team->removePlayer($p1);

		$this->assertSame(0, count($team->positionsAndPlayers[PLAYER_POSITION_DEFENCE]));
		$this->assertSame(1, count($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD]));
		$this->assertSame($p1, $team->removedPlayers[1]);
	}

	public function testRemovePlayerClearsFreeKickPlayer(): void {
		$team = new SimulationTeam(1);
		$p1 = $this->makePlayer(1, PLAYER_POSITION_MIDFIELD, 'OM', $team);
		$team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $p1;
		$team->freeKickPlayer = $p1;

		$team->removePlayer($p1);

		$this->assertNull($team->freeKickPlayer);
	}

	public function testRemovePlayerKeepsFreeKickPlayerIfDifferent(): void {
		$team = new SimulationTeam(1);
		$p1 = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 'IV', $team);
		$p2 = $this->makePlayer(2, PLAYER_POSITION_MIDFIELD, 'OM', $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p1;
		$team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $p2;
		$team->freeKickPlayer = $p2;

		$team->removePlayer($p1);

		$this->assertSame($p2, $team->freeKickPlayer);
	}

	public function testComputeTotalStrengthSumsPlayerStrengths(): void {
		$ws = $this->mockWebsoccer([
			'sim_weight_strength' => 50,
			'sim_weight_strengthTech' => 20,
			'sim_weight_strengthStamina' => 10,
			'sim_weight_strengthFreshness' => 10,
			'sim_weight_strengthSatisfaction' => 10,
			'sim_home_field_advantage' => 5,
			'sim_createformation_strength' => 60,
		]);
		$team = new SimulationTeam(1);
		$guest = new SimulationTeam(2);
		$match = new SimulationMatch(1, $team, $guest, 1);

		$p1 = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 'IV', $team);
		$p2 = $this->makePlayer(2, PLAYER_POSITION_MIDFIELD, 'ZM', $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p1;
		$team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $p2;

		$expected = $p1->getTotalStrength($ws, $match) + $p2->getTotalStrength($ws, $match);
		$this->assertSame($expected, $team->computeTotalStrength($ws, $match));
	}

	public function testComputeTotalStrengthZeroForEmptyTeam(): void {
		$ws = $this->mockWebsoccer([
			'sim_weight_strength' => 50,
			'sim_weight_strengthTech' => 20,
			'sim_weight_strengthStamina' => 10,
			'sim_weight_strengthFreshness' => 10,
			'sim_weight_strengthSatisfaction' => 10,
		]);
		$team = new SimulationTeam(1);
		$guest = new SimulationTeam(2);
		$match = new SimulationMatch(1, $team, $guest, 1);

		$this->assertSame(0, $team->computeTotalStrength($ws, $match));
	}

	public function testCleanReferencesUnsetsArrays(): void {
		$team = new SimulationTeam(1);
		$team->substitutions = [];
		$team->playersOnBench = [];
		$team->removedPlayers = [];
		$team->cleanReferences();
		$this->assertFalse(isset($team->substitutions));
		$this->assertFalse(isset($team->positionsAndPlayers));
		$this->assertFalse(isset($team->playersOnBench));
		$this->assertFalse(isset($team->removedPlayers));
	}
}
