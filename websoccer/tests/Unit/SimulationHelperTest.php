<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SimulationHelper.
 */
final class SimulationHelperTest extends TestCaseBase {
	private function makePlayer(int $id, string $position, int $strength = 80, ?SimulationTeam $team = null): SimulationPlayer {
		if ($team === null) {
			$team = new SimulationTeam(1, 50);
		}
		return new SimulationPlayer($id, $team, $position, 'IV', 3.0, 25, $strength, 70, 60, 90, 85);
	}

	public function testGetMagicNumberReturnsValueInRange(): void {
		$n = SimulationHelper::getMagicNumber();
		$this->assertGreaterThanOrEqual(1, $n);
		$this->assertLessThanOrEqual(100, $n);
	}

	public function testGetMagicNumberWithCustomRange(): void {
		$n = SimulationHelper::getMagicNumber(10, 20);
		$this->assertGreaterThanOrEqual(10, $n);
		$this->assertLessThanOrEqual(20, $n);
	}

	public function testGetMagicNumberReturnsMinWhenMinEqualsMax(): void {
		$this->assertSame(5, SimulationHelper::getMagicNumber(5, 5));
	}

	public function testSelectItemFromProbabilitiesReturnsValidKey(): void {
		$probs = ['a' => 30, 'b' => 70];
		$result = SimulationHelper::selectItemFromProbabilities($probs);
		$this->assertContains($result, ['a', 'b']);
	}

	public function testSelectItemFromProbabilitiesSingleItem(): void {
		$probs = ['only' => 100];
		$this->assertSame('only', SimulationHelper::selectItemFromProbabilities($probs));
	}

	public function testGetPositionsMappingReturnsExpectedKeys(): void {
		$mapping = SimulationHelper::getPositionsMapping();
		$this->assertSame('Torwart', $mapping['T']);
		$this->assertSame('Abwehr', $mapping['LV']);
		$this->assertSame('Abwehr', $mapping['IV']);
		$this->assertSame('Abwehr', $mapping['RV']);
		$this->assertSame('Mittelfeld', $mapping['DM']);
		$this->assertSame('Mittelfeld', $mapping['OM']);
		$this->assertSame('Mittelfeld', $mapping['ZM']);
		$this->assertSame('Mittelfeld', $mapping['LM']);
		$this->assertSame('Mittelfeld', $mapping['RM']);
		$this->assertSame('Sturm', $mapping['LS']);
		$this->assertSame('Sturm', $mapping['MS']);
		$this->assertSame('Sturm', $mapping['RS']);
	}

	public function testSortByStrengthDescending(): void {
		$a = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 60);
		$b = $this->makePlayer(2, PLAYER_POSITION_DEFENCE, 90);
		$this->assertGreaterThan(0, SimulationHelper::sortByStrength($a, $b));
		$this->assertLessThan(0, SimulationHelper::sortByStrength($b, $a));
		$this->assertSame(0, SimulationHelper::sortByStrength($a, $a));
	}

	public function testGetOpponentTeamReturnsGuestForHomePlayer(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 1);
		$p = $this->makePlayer(10, PLAYER_POSITION_DEFENCE, 80, $home);

		$this->assertSame($guest, SimulationHelper::getOpponentTeam($p, $match));
	}

	public function testGetOpponentTeamReturnsHomeForGuestPlayer(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 1);
		$p = $this->makePlayer(10, PLAYER_POSITION_DEFENCE, 80, $guest);

		$this->assertSame($home, SimulationHelper::getOpponentTeam($p, $match));
	}

	public function testGetOpponentTeamOfTeamReturnsGuestForHome(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 1);

		$this->assertSame($guest, SimulationHelper::getOpponentTeamOfTeam($home, $match));
	}

	public function testGetOpponentTeamOfTeamReturnsHomeForGuest(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 1);

		$this->assertSame($home, SimulationHelper::getOpponentTeamOfTeam($guest, $match));
	}

	public function testSelectPlayerReturnsPlayerFromPosition(): void {
		$team = new SimulationTeam(1, 50);
		$p1 = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $team);
		$p2 = $this->makePlayer(2, PLAYER_POSITION_DEFENCE, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p1;
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p2;

		$selected = SimulationHelper::selectPlayer($team, PLAYER_POSITION_DEFENCE);
		$this->assertContains($selected, [$p1, $p2]);
	}

	public function testSelectPlayerFallsBackFromStrikerToMidfield(): void {
		$team = new SimulationTeam(1, 50);
		$p = $this->makePlayer(1, PLAYER_POSITION_MIDFIELD, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $p;

		$selected = SimulationHelper::selectPlayer($team, PLAYER_POSITION_STRIKER);
		$this->assertSame($p, $selected);
	}

	public function testSelectPlayerFallsBackFromMidfieldToDefence(): void {
		$team = new SimulationTeam(1, 50);
		$p = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p;

		$selected = SimulationHelper::selectPlayer($team, PLAYER_POSITION_MIDFIELD);
		$this->assertSame($p, $selected);
	}

	public function testSelectPlayerFallsBackFromDefenceToGoaly(): void {
		$team = new SimulationTeam(1, 50);
		$p = $this->makePlayer(1, PLAYER_POSITION_GOALY, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_GOALY][] = $p;

		$selected = SimulationHelper::selectPlayer($team, PLAYER_POSITION_DEFENCE);
		$this->assertSame($p, $selected);
	}

	public function testSelectPlayerExcludesPlayer(): void {
		$team = new SimulationTeam(1, 50);
		$p1 = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $team);
		$p2 = $this->makePlayer(2, PLAYER_POSITION_DEFENCE, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p1;
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p2;

		$selected = SimulationHelper::selectPlayer($team, PLAYER_POSITION_DEFENCE, $p1);
		$this->assertSame($p2, $selected);
	}

	public function testGetPlayersForPenaltyShootingSortsByStrengthAndAppendsGoaly(): void {
		$team = new SimulationTeam(1, 50);
		$gk = new SimulationPlayer(1, $team, PLAYER_POSITION_GOALY, 'T', 3.0, 25, 50, 70, 60, 90, 85);
		$def = $this->makePlayer(2, PLAYER_POSITION_DEFENCE, 70, $team);
		$st = $this->makePlayer(3, PLAYER_POSITION_STRIKER, 90, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_GOALY][] = $gk;
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $def;
		$team->positionsAndPlayers[PLAYER_POSITION_STRIKER][] = $st;

		$players = SimulationHelper::getPlayersForPenaltyShooting($team);

		// goalkeeper should be last
		$this->assertSame($gk, end($players));
		// strongest field player first
		$this->assertSame($st, $players[0]);
		$this->assertSame($def, $players[1]);
	}

	public function testGetPlayersForPenaltyShootingWithoutGoaly(): void {
		$team = new SimulationTeam(1, 50);
		$def = $this->makePlayer(2, PLAYER_POSITION_DEFENCE, 70, $team);
		$st = $this->makePlayer(3, PLAYER_POSITION_STRIKER, 90, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $def;
		$team->positionsAndPlayers[PLAYER_POSITION_STRIKER][] = $st;

		$players = SimulationHelper::getPlayersForPenaltyShooting($team);
		$this->assertSame(2, count($players));
		$this->assertSame($st, $players[0]);
	}

	public function testCheckAndExecuteSubstitutionsDoesNothingWithEmptySubs(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 10);
		$home->substitutions = [];

		SimulationHelper::checkAndExecuteSubstitutions($match, $home, []);
		$this->assertFalse(isset($home->removedPlayers));
	}

	public function testCheckAndExecuteSubstitutionsExecutesMatchingSub(): void {
		\WebSoccer::setInstanceForTesting($this->mockWebsoccer([
			'sim_strength_reduction_wrongposition' => 10,
			'sim_strength_reduction_secondary' => 5,
		]));

		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 30);

		$out = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $out;

		$in = new SimulationPlayer(2, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[2] = $in;

		$sub = new SimulationSubstitution(30, $in, $out, null, 'ZM');
		$home->substitutions = [$sub];

		$observer = $this->createMock(\ISimulatorObserver::class);
		$observer->expects($this->once())->method('onSubstitution');

		SimulationHelper::checkAndExecuteSubstitutions($match, $home, [$observer]);

		$this->assertTrue(isset($home->removedPlayers[1]));
		$this->assertFalse(isset($home->playersOnBench[2]));
		$this->assertContains($in, $home->positionsAndPlayers[$in->position]);
	}

	public function testCheckAndExecuteSubstitutionsSkipsAlreadyRemovedPlayer(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 30);

		$out = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $home);
		$home->removedPlayers[1] = $out;

		$in = new SimulationPlayer(2, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[2] = $in;

		$sub = new SimulationSubstitution(30, $in, $out);
		$home->substitutions = [$sub];

		$observer = $this->createMock(\ISimulatorObserver::class);
		$observer->expects($this->never())->method('onSubstitution');

		SimulationHelper::checkAndExecuteSubstitutions($match, $home, [$observer]);
	}

	public function testCheckAndExecuteSubstitutionsSkipsWhenPlayerNotOnBench(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 30);

		$out = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $out;

		$in = new SimulationPlayer(2, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);

		$sub = new SimulationSubstitution(30, $in, $out);
		$home->substitutions = [$sub];

		$observer = $this->createMock(\ISimulatorObserver::class);
		$observer->expects($this->never())->method('onSubstitution');

		SimulationHelper::checkAndExecuteSubstitutions($match, $home, [$observer]);
	}

	public function testCreateUnplannedSubstitutionReturnsFalseWithEmptyBench(): void {
		$team = new SimulationTeam(1, 50);
		$team->playersOnBench = [];
		$team->substitutions = [];
		$p = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $team);

		$this->assertFalse(SimulationHelper::createUnplannedSubstitutionForPlayer(15, $p));
	}

	public function testCreateUnplannedSubstitutionCreatesSub(): void {
		$team = new SimulationTeam(1, 50);
		$team->substitutions = [];

		$out = $this->makePlayer(1, PLAYER_POSITION_DEFENCE, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $out;

		$bench = new SimulationPlayer(2, $team, PLAYER_POSITION_DEFENCE, 'IV', 3.0, 25, 70, 70, 60, 90, 85);
		$team->playersOnBench[2] = $bench;

		$result = SimulationHelper::createUnplannedSubstitutionForPlayer(15, $out);
		$this->assertTrue($result);
		$this->assertCount(1, $team->substitutions);
		$this->assertSame(15, $team->substitutions[0]->minute);
		$this->assertSame($bench, $team->substitutions[0]->playerIn);
		$this->assertSame($out, $team->substitutions[0]->playerOut);
	}

	public function testCreateUnplannedSubstitutionReturnsFalseWhenNoMatchingPositionOnBench(): void {
		$team = new SimulationTeam(1, 50);
		$team->substitutions = [];

		$out = $this->makePlayer(1, PLAYER_POSITION_GOALY, 80, $team);
		$team->positionsAndPlayers[PLAYER_POSITION_GOALY][] = $out;

		$bench = new SimulationPlayer(2, $team, PLAYER_POSITION_STRIKER, 'MS', 3.0, 25, 70, 70, 60, 90, 85);
		$team->playersOnBench[2] = $bench;

		$result = SimulationHelper::createUnplannedSubstitutionForPlayer(15, $out);
		$this->assertFalse($result);
	}
}
