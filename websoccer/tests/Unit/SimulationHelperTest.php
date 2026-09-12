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

		// the substitution slot must be freed, since the substitution will never be executed (see issue #7).
		$this->assertSame(999, $sub->minute);
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

		// the substitution slot must be freed, since the substitution will never be executed (see issue #7).
		$this->assertSame(999, $sub->minute);
	}

	/**
	 * Issue #7: a player who got sent off the pitch after his second yellow card cannot be
	 * substituted anymore. The planned substitution must not be executed, but its slot must be
	 * freed (marked as unreachable) so that the manager can still plan another substitution.
	 *
	 * @see https://github.com/ihofmann/open-websoccer/issues/7
	 */
	public function testSubstitutionForPlayerSentOffAfterSecondYellowCardIsNotExecutedAndSlotIsFreed(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 55);

		// player on the pitch for whom a substitution is planned at minute 60.
		$out = $this->makePlayer(1, PLAYER_POSITION_MIDFIELD, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $out;

		// player on the bench who is supposed to come in.
		$in = new SimulationPlayer(2, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[2] = $in;

		$sub = new SimulationSubstitution(60, $in, $out);
		$home->substitutions = [$sub];

		$observer = new DefaultSimulationObserver();

		// first yellow card: the player may stay on the pitch, so the substitution is still possible.
		$observer->onYellowCard($match, $out);
		$this->assertSame(1, $out->yellowCards);
		$this->assertFalse(isset($home->removedPlayers[1]));

		// second yellow card: the player is sent off, so his planned substitution can never be executed.
		$observer->onYellowCard($match, $out);
		$this->assertSame(2, $out->yellowCards);
		$this->assertTrue(isset($home->removedPlayers[1]));

		$observerMock = $this->createMock(\ISimulatorObserver::class);
		$observerMock->expects($this->never())->method('onSubstitution');

		// the planned substitution minute is reached.
		$match->minute = 60;
		SimulationHelper::checkAndExecuteSubstitutions($match, $home, [$observerMock]);

		// the substitution must not be executed.
		$this->assertTrue(isset($home->playersOnBench[2]));

		// but it must be marked as unreachable, so that the slot does not remain blocked forever.
		$this->assertSame(999, $sub->minute);
	}

	/**
	 * A single yellow card does not send a player off, so a planned substitution
	 * for him must be executed as usual (see issue #7).
	 */
	public function testSingleYellowCardDoesNotPreventPlannedSubstitution(): void {
		\WebSoccer::setInstanceForTesting($this->mockWebsoccer([
			'sim_strength_reduction_wrongposition' => 10,
			'sim_strength_reduction_secondary' => 5,
		]));

		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 59);

		$out = $this->makePlayer(1, PLAYER_POSITION_MIDFIELD, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $out;

		$in = new SimulationPlayer(2, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[2] = $in;

		$sub = new SimulationSubstitution(60, $in, $out);
		$home->substitutions = [$sub];

		// first yellow card only: the player stays on the pitch.
		(new DefaultSimulationObserver())->onYellowCard($match, $out);

		$match->minute = 60;
		SimulationHelper::checkAndExecuteSubstitutions($match, $home, []);

		// the substitution is executed as planned.
		$this->assertTrue(isset($home->removedPlayers[1]));
		$this->assertFalse(isset($home->playersOnBench[2]));
		$this->assertContains($in, $home->positionsAndPlayers[$in->position]);
		$this->assertSame(60, $sub->minute);
	}

	/**
	 * Issue #7: if the player who is supposed to come in is not on the bench anymore
	 * (e.g. because he entered the pitch through an earlier substitution), the planned
	 * substitution must not be executed, but its slot must be freed.
	 *
	 * @see https://github.com/ihofmann/open-websoccer/issues/7
	 */
	public function testSubstitutionWithPlayerInNotOnBenchAnymoreIsNotExecutedAndSlotIsFreed(): void {
		\WebSoccer::setInstanceForTesting($this->mockWebsoccer([
			'sim_strength_reduction_wrongposition' => 10,
			'sim_strength_reduction_secondary' => 5,
		]));

		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 30);

		$a = $this->makePlayer(1, PLAYER_POSITION_MIDFIELD, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $a;
		$c = $this->makePlayer(3, PLAYER_POSITION_DEFENCE, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $c;

		$b = new SimulationPlayer(2, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[2] = $b;

		// valid substitution at minute 30: A goes out, B comes in.
		$firstSub = new SimulationSubstitution(30, $b, $a);
		// invalid substitution at minute 60: B is already on the pitch since minute 30.
		$secondSub = new SimulationSubstitution(60, $b, $c);
		$home->substitutions = [$firstSub, $secondSub];

		// minute 30: the first substitution is executed.
		$match->minute = 30;
		SimulationHelper::checkAndExecuteSubstitutions($match, $home, []);
		$this->assertTrue(isset($home->removedPlayers[1]));
		$this->assertFalse(isset($home->playersOnBench[2]));

		// minute 60: the second substitution cannot be executed anymore.
		$match->minute = 60;
		SimulationHelper::checkAndExecuteSubstitutions($match, $home, []);
		$this->assertFalse(isset($home->removedPlayers[3]));

		// but it must be marked as unreachable, so that the slot does not remain blocked forever.
		$this->assertSame(999, $secondSub->minute);
	}

	/**
	 * Issue #7: a planned substitution which became unreachable (because its player was sent off
	 * after a yellow-red card) must be the first one to be replaced by an unplanned substitution
	 * (e.g. after an injury), rather than destroying a still valid planned substitution.
	 *
	 * @see https://github.com/ihofmann/open-websoccer/issues/7
	 */
	public function testUnplannedSubstitutionReplacesUnreachablePlannedSubstitutionBeforeValidOnes(): void {
		$home = new SimulationTeam(1, 50);
		$guest = new SimulationTeam(2, 50);
		$match = new SimulationMatch(1, $home, $guest, 55);

		// player who will be sent off after his second yellow card, with a planned substitution at minute 60.
		$out = $this->makePlayer(1, PLAYER_POSITION_MIDFIELD, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $out;
		$benchPlayer = new SimulationPlayer(2, $home, PLAYER_POSITION_STRIKER, 'MS', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[2] = $benchPlayer;
		$deadSub = new SimulationSubstitution(60, $benchPlayer, $out);

		// two more, still valid planned substitutions in the future.
		$x = $this->makePlayer(3, PLAYER_POSITION_DEFENCE, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $x;
		$y = $this->makePlayer(4, PLAYER_POSITION_DEFENCE, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $y;
		$benchC = new SimulationPlayer(5, $home, PLAYER_POSITION_STRIKER, 'MS', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[5] = $benchC;
		$benchD = new SimulationPlayer(6, $home, PLAYER_POSITION_STRIKER, 'MS', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[6] = $benchD;
		$validSub1 = new SimulationSubstitution(75, $benchC, $x);
		$validSub2 = new SimulationSubstitution(80, $benchD, $y);

		$home->substitutions = [$deadSub, $validSub1, $validSub2];

		// the player is sent off after his second yellow card.
		$observer = new DefaultSimulationObserver();
		$observer->onYellowCard($match, $out);
		$observer->onYellowCard($match, $out);

		// at minute 60, his planned substitution is skipped and thus becomes unreachable.
		$match->minute = 60;
		SimulationHelper::checkAndExecuteSubstitutions($match, $home, []);
		$this->assertSame(999, $deadSub->minute);

		// at minute 65, another player gets injured and needs to be substituted.
		$match->minute = 65;
		$injured = $this->makePlayer(7, PLAYER_POSITION_MIDFIELD, 80, $home);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $injured;
		$benchE = new SimulationPlayer(8, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 75, 70, 60, 90, 85);
		$home->playersOnBench[8] = $benchE;

		$result = SimulationHelper::createUnplannedSubstitutionForPlayer(66, $injured);
		$this->assertTrue($result);

		// the unplanned substitution replaces the unreachable one...
		$this->assertSame($benchE, $home->substitutions[0]->playerIn);
		$this->assertSame($injured, $home->substitutions[0]->playerOut);
		$this->assertSame(66, $home->substitutions[0]->minute);
		// ...while the still valid planned substitutions remain untouched.
		$this->assertSame($validSub1, $home->substitutions[1]);
		$this->assertSame($validSub2, $home->substitutions[2]);
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
