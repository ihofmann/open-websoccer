<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SimulationPlayer.
 */
final class SimulationPlayerTest extends TestCaseBase {
	private function makeTeam(int $id = 1, int $offensive = 50): SimulationTeam {
		return new SimulationTeam($id, $offensive);
	}

	private function makePlayer(int $id = 10, ?SimulationTeam $team = null): SimulationPlayer {
		if ($team === null) {
			$team = $this->makeTeam();
		}
		return new SimulationPlayer(
			$id, $team, PLAYER_POSITION_DEFENCE, 'IV',
			3.0, 25, 80, 70, 60, 90, 85
		);
	}

	private function simConfig(): array {
		return [
			'sim_weight_strength' => 50,
			'sim_weight_strengthTech' => 20,
			'sim_weight_strengthStamina' => 10,
			'sim_weight_strengthFreshness' => 10,
			'sim_weight_strengthSatisfaction' => 10,
			'sim_home_field_advantage' => 5,
			'sim_createformation_strength' => 60,
		];
	}

	public function testConstructorSetsAllProperties(): void {
		$team = $this->makeTeam();
		$p = new SimulationPlayer(5, $team, PLAYER_POSITION_STRIKER, 'MS',
			2.5, 28, 90, 85, 75, 80, 88);

		$this->assertSame(5, $p->id);
		$this->assertSame($team, $p->team);
		$this->assertSame(PLAYER_POSITION_STRIKER, $p->position);
		$this->assertSame('MS', $p->mainPosition);
		$this->assertSame(28, $p->age);
		$this->assertSame(90, $p->strength);
		$this->assertSame(85, $p->strengthTech);
		$this->assertSame(75, $p->strengthStamina);
		$this->assertSame(80, $p->strengthFreshness);
		$this->assertSame(88, $p->strengthSatisfaction);
		$this->assertEquals(2.5, $p->getMark());
	}

	public function testConstructorInitializesDefaultState(): void {
		$p = $this->makePlayer();
		$this->assertSame(0, $p->injured);
		$this->assertSame(0, $p->blocked);
		$this->assertSame(0, $p->goals);
		$this->assertSame(0, $p->getMinutesPlayed());
		$this->assertSame(0, $p->getBallContacts());
		$this->assertSame(0, $p->getWonTackles());
		$this->assertSame(0, $p->getLostTackles());
		$this->assertSame(0, $p->getShoots());
		$this->assertSame(0, $p->getPassesSuccessed());
		$this->assertSame(0, $p->getPassesFailed());
		$this->assertSame(0, $p->getAssists());
	}

	public function testGetMarkReturnsMark(): void {
		$p = $this->makePlayer();
		$this->assertSame(3.0, $p->getMark());
	}

	public function testSetMarkChangesMark(): void {
		$p = $this->makePlayer();
		$p->setMark(2.0);
		$this->assertSame(2.0, $p->getMark());
	}

	public function testImproveMarkReducesMarkButNotBelowOne(): void {
		$p = $this->makePlayer();
		$p->improveMark(2.5);
		$this->assertEquals(1.0, $p->getMark());
	}

	public function testImproveMarkBySmallAmount(): void {
		$p = $this->makePlayer();
		$p->improveMark(0.5);
		$this->assertSame(2.5, $p->getMark());
	}

	public function testDowngradeMarkIncreasesMarkButNotAboveSix(): void {
		$p = $this->makePlayer();
		$p->downgradeMark(4.0);
		$this->assertEquals(6.0, $p->getMark());
	}

	public function testDowngradeMarkBySmallAmount(): void {
		$p = $this->makePlayer();
		$p->downgradeMark(1.0);
		$this->assertSame(4.0, $p->getMark());
	}

	public function testSetMarkTriggersStrengthRecomputation(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 1);

		$p = $this->makePlayer(10, $home);
		$initial = $p->getTotalStrength($ws, $match);
		$this->assertGreaterThan(0, $initial);

		$p->setMark(1.0);
		$improved = $p->getTotalStrength($ws, $match);
		$this->assertGreaterThan($initial, $improved);
	}

	public function testGetTotalStrengthIsCached(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 1);

		$p = $this->makePlayer(10, $home);
		$this->assertSame($p->getTotalStrength($ws, $match), $p->getTotalStrength($ws, $match));
	}

	public function testGetTotalStrengthDoesNotExceedHundred(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 1);
		$match->isSoldOut = TRUE;

		$p = new SimulationPlayer(1, $home, PLAYER_POSITION_DEFENCE, 'IV',
			1.0, 20, 100, 100, 100, 100, 100);
		$this->assertLessThanOrEqual(100, $p->getTotalStrength($ws, $match));
	}

	public function testGetTotalStrengthAppliesHomeFieldAdvantage(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 1);
		$match->isSoldOut = TRUE;

		$p = $this->makePlayer(10, $home);
		$withAdv = $p->getTotalStrength($ws, $match);

		$p2 = $this->makePlayer(10, $guest);
		$withoutAdv = $p2->getTotalStrength($ws, $match);

		$this->assertGreaterThan($withoutAdv, $withAdv);
	}

	public function testGetTotalStrengthReducesForNoFormationTeam(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$home = $this->makeTeam(1);
		$home->noFormationSet = TRUE;
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 1);

		$p = $this->makePlayer(10, $home);
		$reduced = $p->getTotalStrength($ws, $match);

		$home->noFormationSet = FALSE;
		$p->setMark(2.0); // trigger recompute with new mark
		$normal = $p->getTotalStrength($ws, $match);

		$this->assertLessThan($normal, $reduced);
	}

	public function testSetAndGetGoals(): void {
		$p = $this->makePlayer();
		$p->setGoals(3);
		$this->assertSame(3, $p->getGoals());
	}

	public function testSetAndGetWonTackles(): void {
		$p = $this->makePlayer();
		$p->setWonTackles(7);
		$this->assertSame(7, $p->getWonTackles());
	}

	public function testSetAndGetLostTackles(): void {
		$p = $this->makePlayer();
		$p->setLostTackles(4);
		$this->assertSame(4, $p->getLostTackles());
	}

	public function testSetAndGetShoots(): void {
		$p = $this->makePlayer();
		$p->setShoots(5);
		$this->assertSame(5, $p->getShoots());
	}

	public function testSetAndGetBallContacts(): void {
		$p = $this->makePlayer();
		$p->setBallContacts(42);
		$this->assertSame(42, $p->getBallContacts());
	}

	public function testSetAndGetPassesSuccessed(): void {
		$p = $this->makePlayer();
		$p->setPassesSuccessed(15);
		$this->assertSame(15, $p->getPassesSuccessed());
	}

	public function testSetAndGetPassesFailed(): void {
		$p = $this->makePlayer();
		$p->setPassesFailed(8);
		$this->assertSame(8, $p->getPassesFailed());
	}

	public function testSetAndGetAssists(): void {
		$p = $this->makePlayer();
		$p->setAssists(2);
		$this->assertSame(2, $p->getAssists());
	}

	public function testSetMinutesPlayedOnlyIncreases(): void {
		$p = $this->makePlayer();
		$p->setMinutesPlayed(10);
		$this->assertSame(10, $p->getMinutesPlayed());
		$p->setMinutesPlayed(5);
		$this->assertSame(10, $p->getMinutesPlayed());
	}

	public function testSetMinutesPlayedAtMultipleOfTwentyLosesFreshness(): void {
		$p = $this->makePlayer();
		$initialFreshness = $p->strengthFreshness;
		$p->setMinutesPlayed(20);
		$this->assertLessThan($initialFreshness, $p->strengthFreshness);
	}

	public function testSetMinutesPlayedGoalyLosesOnlyOneFreshnessAtTwenty(): void {
		$team = $this->makeTeam();
		$p = new SimulationPlayer(1, $team, PLAYER_POSITION_GOALY, 'T',
			3.0, 25, 80, 70, 60, 90, 85);
		$initialFreshness = $p->strengthFreshness;
		$p->setMinutesPlayed(20);
		$this->assertSame($initialFreshness - 1, $p->strengthFreshness);
	}

	public function testSetMinutesPlayedNoFreshnessLossBeforeTwenty(): void {
		$p = $this->makePlayer();
		$initialFreshness = $p->strengthFreshness;
		$p->setMinutesPlayed(19);
		$this->assertSame($initialFreshness, $p->strengthFreshness);
	}

	public function testSetMinutesPlayedWithoutFreshnessRecompute(): void {
		$p = $this->makePlayer();
		$initialFreshness = $p->strengthFreshness;
		$p->setMinutesPlayed(20, FALSE);
		$this->assertSame(20, $p->getMinutesPlayed());
		$this->assertSame($initialFreshness, $p->strengthFreshness);
	}

	public function testSetMinutesPlayedOlderPlayerLosesMoreFreshness(): void {
		$team = $this->makeTeam();
		$youngPlayer = new SimulationPlayer(1, $team, PLAYER_POSITION_MIDFIELD, 'ZM',
			3.0, 22, 80, 70, 60, 90, 85);
		$oldPlayer = new SimulationPlayer(2, $team, PLAYER_POSITION_MIDFIELD, 'ZM',
			3.0, 35, 80, 70, 60, 90, 85);

		$youngFresh = $youngPlayer->strengthFreshness;
		$oldFresh = $oldPlayer->strengthFreshness;

		$youngPlayer->setMinutesPlayed(20);
		$oldPlayer->setMinutesPlayed(20);

		// Old player (age > 32) loses 2 freshness; young player loses 1.
		// So old player should have lower freshness.
		$this->assertLessThan($youngPlayer->strengthFreshness, $oldPlayer->strengthFreshness);
	}

	public function testCleanReferencesUnsetsTeam(): void {
		$team = $this->makeTeam();
		$p = $this->makePlayer(10, $team);
		$p->cleanReferences();
		$this->assertFalse(isset($p->team));
	}

	public function testToStringReturnsKeyAttributes(): void {
		$team = $this->makeTeam(7);
		$p = $this->makePlayer(10, $team);
		$str = (string) $p;
		$this->assertStringContainsString('id: 10', $str);
		$this->assertStringContainsString('team: 7', $str);
		$this->assertStringContainsString('position: Abwehr', $str);
	}
}
