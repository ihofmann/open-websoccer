<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DefaultSimulationObserver.
 */
final class DefaultSimulationObserverTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position = PLAYER_POSITION_STRIKER, float $mark = 3.0): SimulationPlayer {
		return new SimulationPlayer($id, $team, $position, 'MS', $mark, 25, 80, 70, 60, 90, 85);
	}

	private function makeMatch(?SimulationTeam $home = null, ?SimulationTeam $guest = null): SimulationMatch {
		if ($home === null) { $home = $this->makeTeam(1); }
		if ($guest === null) { $guest = $this->makeTeam(2); }
		return new SimulationMatch(1, $home, $guest, 10);
	}

	public function testOnGoalIncrementsScorerGoalsAndTeamGoals(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$scorer = $this->makePlayer(10, $home);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs = new DefaultSimulationObserver();
		$obs->onGoal($match, $scorer, $goaly);

		$this->assertSame(1, $scorer->getGoals());
		$this->assertSame(1, $home->getGoals());
		$this->assertSame(1, $scorer->getShoots());
	}

	public function testOnGoalImprovesScorerMark(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$scorer = $this->makePlayer(10, $home, PLAYER_POSITION_STRIKER, 3.0);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs = new DefaultSimulationObserver();
		$obs->onGoal($match, $scorer, $goaly);

		$this->assertLessThan(3.0, $scorer->getMark());
	}

	public function testOnGoalDowngradesGoalyMark(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$scorer = $this->makePlayer(10, $home);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onGoal($match, $scorer, $goaly);

		$this->assertGreaterThan(3.0, $goaly->getMark());
	}

	public function testOnGoalWithAssistImprovesAssistPlayerMark(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$assister = $this->makePlayer(9, $home, PLAYER_POSITION_MIDFIELD, 3.0);
		$scorer = $this->makePlayer(10, $home);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$match->setPlayerWithBall($assister);
		$match->setPlayerWithBall($scorer);

		$obs = new DefaultSimulationObserver();
		$obs->onGoal($match, $scorer, $goaly);

		$this->assertLessThan(3.0, $assister->getMark());
		$this->assertSame(1, $assister->getAssists());
	}

	public function testOnGoalWithoutAssistDoesNotImprovePreviousPlayer(): void {
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = $this->makeMatch($home, $guest);
		$opponent = $this->makePlayer(9, $guest, PLAYER_POSITION_MIDFIELD, 3.0);
		$scorer = $this->makePlayer(10, $home);
		$goaly = $this->makePlayer(1, $guest, PLAYER_POSITION_GOALY);

		$match->setPlayerWithBall($opponent);
		$match->setPlayerWithBall($scorer);

		$obs = new DefaultSimulationObserver();
		$obs->onGoal($match, $scorer, $goaly);

		// previous player is from opponent team, so no assist
		$this->assertSame(0, $opponent->getAssists());
	}

	public function testOnShootFailureDowngradesScorerAndImprovesGoaly(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$scorer = $this->makePlayer(10, $home, PLAYER_POSITION_STRIKER, 3.0);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onShootFailure($match, $scorer, $goaly);

		$this->assertGreaterThan(3.0, $scorer->getMark());
		$this->assertLessThan(3.0, $goaly->getMark());
		$this->assertSame(1, $scorer->getShoots());
	}

	public function testOnShootFailureDoesNotDowngradeHeroScorer(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$scorer = $this->makePlayer(10, $home, PLAYER_POSITION_STRIKER, 3.0);
		$scorer->setGoals(3);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onShootFailure($match, $scorer, $goaly);

		$this->assertEquals(3.0, $scorer->getMark());
	}

	public function testOnAfterTackleImprovesWinnerAndDowngradesLooser(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$winner = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD, 3.0);
		$looser = $this->makePlayer(20, $match->guestTeam, PLAYER_POSITION_MIDFIELD, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onAfterTackle($match, $winner, $looser);

		$this->assertLessThan(3.0, $winner->getMark());
		$this->assertGreaterThan(3.0, $looser->getMark());
		$this->assertSame(1, $winner->getWonTackles());
	}

	public function testOnBallPassSuccessImprovesMarkAndIncrementsPasses(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onBallPassSuccess($match, $player);

		$this->assertLessThan(3.0, $player->getMark());
		$this->assertSame(1, $player->getPassesSuccessed());
	}

	public function testOnBallPassFailureDowngradesMarkAndIncrementsFailedPasses(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onBallPassFailure($match, $player);

		$this->assertGreaterThan(3.0, $player->getMark());
		$this->assertSame(1, $player->getPassesFailed());
	}

	public function testOnInjurySetsInjuredAndRemovesPlayerWhenNoSubAvailable(): void {
		$home = $this->makeTeam(1);
		$home->playersOnBench = [];
		$home->substitutions = [];
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $player;

		$obs = new DefaultSimulationObserver();
		$obs->onInjury($match, $player, 3);

		$this->assertSame(3, $player->injured);
		$this->assertTrue(isset($home->removedPlayers[10]));
	}

	public function testOnInjuryCreatesUnplannedSubstitutionWhenBenchAvailable(): void {
		$home = $this->makeTeam(1);
		$home->substitutions = [];
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $player;

		$bench = new SimulationPlayer(11, $home, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 70, 70, 60, 90, 85);
		$home->playersOnBench[11] = $bench;

		$obs = new DefaultSimulationObserver();
		$obs->onInjury($match, $player, 2);

		$this->assertSame(2, $player->injured);
		$this->assertCount(1, $home->substitutions);
		// player still on pitch (will be removed when sub executes)
		$this->assertFalse(isset($home->removedPlayers[10]));
	}

	public function testOnYellowCardIncrementsYellowCards(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $player;

		$obs = new DefaultSimulationObserver();
		$obs->onYellowCard($match, $player);

		$this->assertSame(1, $player->yellowCards);
		$this->assertFalse(isset($home->removedPlayers[10]));
	}

	public function testOnYellowCardRemovesPlayerOnSecondYellow(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD);
		$player->yellowCards = 1;
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $player;

		$obs = new DefaultSimulationObserver();
		$obs->onYellowCard($match, $player);

		$this->assertSame(2, $player->yellowCards);
		$this->assertTrue(isset($home->removedPlayers[10]));
	}

	public function testOnRedCardSetsRedCardAndRemovesPlayer(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD);
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $player;

		$obs = new DefaultSimulationObserver();
		$obs->onRedCard($match, $player, 2);

		$this->assertSame(1, $player->redCard);
		$this->assertSame(2, $player->blocked);
		$this->assertTrue(isset($home->removedPlayers[10]));
	}

	public function testOnPenaltyShootSuccessfulIncrementsTeamGoals(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onPenaltyShoot($match, $player, $goaly, TRUE);

		$this->assertSame(1, $home->getGoals());
		$this->assertLessThan(3.0, $player->getMark());
	}

	public function testOnPenaltyShootFailedDowngradesShooterAndImprovesGoaly(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_STRIKER, 3.0);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onPenaltyShoot($match, $player, $goaly, FALSE);

		$this->assertGreaterThan(3.0, $player->getMark());
		$this->assertLessThan(3.0, $goaly->getMark());
		$this->assertSame(0, $home->getGoals());
	}

	public function testOnCornerImprovesPassingPlayerMark(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$passer = $this->makePlayer(10, $home, PLAYER_POSITION_MIDFIELD, 3.0);
		$target = $this->makePlayer(11, $home, PLAYER_POSITION_MIDFIELD);

		$obs = new DefaultSimulationObserver();
		$obs->onCorner($match, $passer, $target);

		$this->assertLessThan(3.0, $passer->getMark());
		$this->assertSame(1, $passer->getPassesSuccessed());
	}

	public function testOnFreeKickSuccessfulIncrementsGoals(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs = new DefaultSimulationObserver();
		$obs->onFreeKick($match, $player, $goaly, TRUE);

		$this->assertSame(1, $player->getGoals());
		$this->assertSame(1, $home->getGoals());
		$this->assertSame(1, $player->getShoots());
	}

	public function testOnFreeKickFailedDowngradesShooter(): void {
		$home = $this->makeTeam(1);
		$match = $this->makeMatch($home, $this->makeTeam(2));
		$player = $this->makePlayer(10, $home, PLAYER_POSITION_STRIKER, 3.0);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY, 3.0);

		$obs = new DefaultSimulationObserver();
		$obs->onFreeKick($match, $player, $goaly, FALSE);

		$this->assertGreaterThan(3.0, $player->getMark());
		$this->assertSame(1, $player->getShoots());
	}
}
