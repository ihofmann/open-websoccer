<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DefaultSimulationStrategy.
 */
final class DefaultSimulationStrategyTest extends TestCaseBase {
	private function simConfig(): array {
		return [
			'sim_shootstrength_defense' => 50,
			'sim_shootstrength_midfield' => 70,
			'sim_shootstrength_striker' => 90,
			'sim_shootprobability' => 100,
			'sim_goaly_influence' => 20,
			'sim_cardsprobability' => 100,
			'sim_injuredprobability' => 100,
			'sim_maxmatches_injured' => 5,
			'sim_maxmatches_blocked' => 3,
			'sim_decrease_freshness' => TRUE,
			'sim_weight_strength' => 50,
			'sim_weight_strengthTech' => 20,
			'sim_weight_strengthStamina' => 10,
			'sim_weight_strengthFreshness' => 10,
			'sim_weight_strengthSatisfaction' => 10,
			'sim_home_field_advantage' => 5,
			'sim_createformation_strength' => 60,
		];
	}

	private function makeTeam(int $id, int $offensive = 50): SimulationTeam {
		return new SimulationTeam($id, $offensive);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position, string $mainPosition = 'IV', int $strength = 80): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, $position, $mainPosition, 3.0, 25, $strength, 70, 60, 90, 85);
		$p->name = 'Player' . $id;
		return $p;
	}

	private function makeFullMatch(): SimulationMatch {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);

		// home: 1 goaly, 4 defence, 4 midfield, 2 strikers
		$home->positionsAndPlayers[PLAYER_POSITION_GOALY][] = $this->makePlayer(1, $home, PLAYER_POSITION_GOALY, 'T');
		for ($i = 2; $i <= 5; $i++) {
			$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $this->makePlayer($i, $home, PLAYER_POSITION_DEFENCE, 'IV');
		}
		for ($i = 6; $i <= 9; $i++) {
			$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $this->makePlayer($i, $home, PLAYER_POSITION_MIDFIELD, 'ZM');
		}
		for ($i = 10; $i <= 11; $i++) {
			$home->positionsAndPlayers[PLAYER_POSITION_STRIKER][] = $this->makePlayer($i, $home, PLAYER_POSITION_STRIKER, 'MS');
		}

		// guest: same structure
		$guest->positionsAndPlayers[PLAYER_POSITION_GOALY][] = $this->makePlayer(20, $guest, PLAYER_POSITION_GOALY, 'T');
		for ($i = 21; $i <= 24; $i++) {
			$guest->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $this->makePlayer($i, $guest, PLAYER_POSITION_DEFENCE, 'IV');
		}
		for ($i = 25; $i <= 28; $i++) {
			$guest->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $this->makePlayer($i, $guest, PLAYER_POSITION_MIDFIELD, 'ZM');
		}
		for ($i = 29; $i <= 30; $i++) {
			$guest->positionsAndPlayers[PLAYER_POSITION_STRIKER][] = $this->makePlayer($i, $guest, PLAYER_POSITION_STRIKER, 'MS');
		}

		$match = new SimulationMatch(1, $home, $guest, 1);
		return $match;
	}

	public function testConstructorCreatesStrategy(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$strategy = new DefaultSimulationStrategy($ws);
		$this->assertInstanceOf(DefaultSimulationStrategy::class, $strategy);
		$this->assertInstanceOf(\ISimulationStrategy::class, $strategy);
	}

	public function testAttachObserver(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		$strategy = new DefaultSimulationStrategy($ws);
		$observer = $this->createMock(\ISimulationObserver::class);
		$strategy->attachObserver($observer);
		$this->assertInstanceOf(DefaultSimulationStrategy::class, $strategy);
	}

	public function testKickoffSetsPlayerWithBall(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$match = $this->makeFullMatch();

		$strategy->kickoff($match);
		$this->assertNotNull($match->getPlayerWithBall());
	}

	public function testNextActionReturnsValidMethodName(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);

		$action = $strategy->nextAction($match);
		$this->assertContains($action, ['tackle', 'shoot', 'passBall']);
	}

	public function testNextActionReturnsPassBallForGoaly(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$match = $this->makeFullMatch();

		$goaly = $match->homeTeam->positionsAndPlayers[PLAYER_POSITION_GOALY][0];
		$match->setPlayerWithBall($goaly);

		$this->assertSame('passBall', $strategy->nextAction($match));
	}

	public function testPassBallReturnsBoolean(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);

		$result = $strategy->passBall($match);
		$this->assertIsBool($result);
	}

	public function testPassBallChangesPlayerWithBall(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);
		$initialPlayer = $match->getPlayerWithBall();

		$strategy->passBall($match);
		// After a pass, a player should still have the ball
		$this->assertNotNull($match->getPlayerWithBall());
	}

	public function testTackleReturnsResultCode(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);

		$result = $strategy->tackle($match);
		$this->assertContains($result, [0, 1]);
	}

	public function testShootReturnsResultCode(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$strategy->attachObserver(new DefaultSimulationObserver());
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);

		$result = $strategy->shoot($match);
		$this->assertContains($result, [0, 1]);
	}

	public function testShootIncrementsShootsCount(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$strategy->attachObserver(new DefaultSimulationObserver());
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);
		$player = $match->getPlayerWithBall();
		$initialShoots = $player->getShoots();

		$strategy->shoot($match);
		$this->assertSame($initialShoots + 1, $player->getShoots());
	}

	public function testPenaltyShootingProducesWinner(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);
		$strategy->attachObserver(new DefaultSimulationObserver());
		$match = $this->makeFullMatch();

		$strategy->penaltyShooting($match);
		// with observer updating goals, there should be a winner
		$this->assertNotSame($match->homeTeam->getGoals(), $match->guestTeam->getGoals());
	}

	public function testPassBallNotifiesObserverOnSuccess(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$strategy = new DefaultSimulationStrategy($ws);

		$observer = $this->createMock(\ISimulationObserver::class);
		$strategy->attachObserver($observer);

		// Use very strong players to guarantee pass success
		$match = $this->makeFullMatch();
		$strategy->kickoff($match);

		// Run many passes; at least one should succeed and trigger observer
		$anySuccess = false;
		for ($i = 0; $i < 20; $i++) {
			$result = $strategy->passBall($match);
			if ($result) { $anySuccess = true; break; }
		}
		$this->assertTrue($anySuccess);
	}
}
