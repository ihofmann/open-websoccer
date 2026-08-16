<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for Simulator.
 */
final class SimulatorTest extends TestCaseBase {
	private function simConfig(): array {
		return [
			'sim_strategy' => 'DefaultSimulationStrategy',
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
			'sim_interval' => 90,
			'sim_weight_strength' => 50,
			'sim_weight_strengthTech' => 20,
			'sim_weight_strengthStamina' => 10,
			'sim_weight_strengthFreshness' => 10,
			'sim_weight_strengthSatisfaction' => 10,
			'sim_home_field_advantage' => 5,
			'sim_createformation_strength' => 60,
			'supported_languages' => 'en',
		];
	}

	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position): SimulationPlayer {
		return new SimulationPlayer($id, $team, $position, 'IV', 3.0, 25, 80, 70, 60, 90, 85);
	}

	private function makeFullTeam(int $id): SimulationTeam {
		$team = $this->makeTeam($id);
		$team->substitutions = [];
		$team->positionsAndPlayers[PLAYER_POSITION_GOALY][] = $this->makePlayer(1, $team, PLAYER_POSITION_GOALY);
		for ($i = 2; $i <= 5; $i++) {
			$team->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $this->makePlayer($i, $team, PLAYER_POSITION_DEFENCE);
		}
		for ($i = 6; $i <= 9; $i++) {
			$team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $this->makePlayer($i, $team, PLAYER_POSITION_MIDFIELD);
		}
		for ($i = 10; $i <= 11; $i++) {
			$team->positionsAndPlayers[PLAYER_POSITION_STRIKER][] = $this->makePlayer($i, $team, PLAYER_POSITION_STRIKER);
		}
		return $team;
	}

	public function testConstructorCreatesSimulatorWithStrategy(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$db = $this->mockDb();

		$sim = new Simulator($db, $ws);
		$this->assertInstanceOf(Simulator::class, $sim);
		$this->assertInstanceOf(\ISimulationStrategy::class, $sim->getSimulationStrategy());
	}

	public function testConstructorThrowsOnInvalidStrategyClass(): void {
		$config = $this->simConfig();
		$config['sim_strategy'] = 'NonExistentStrategyClass';
		$ws = $this->mockWebsoccer($config);
		$db = $this->mockDb();

		$this->expectException(\Exception::class);
		new Simulator($db, $ws);
	}

	public function testAttachObserver(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$db = $this->mockDb();

		$sim = new Simulator($db, $ws);
		$observer = $this->createMock(\ISimulatorObserver::class);
		$sim->attachObserver($observer);
		$this->assertInstanceOf(Simulator::class, $sim);
	}

	public function testSimulateMatchCompletesMatchWithNoPlayers(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);
		$db = $this->mockDb();

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 0);

		$sim = new Simulator($db, $ws);
		$sim->simulateMatch($match, 90);
		$this->assertTrue($match->isCompleted);
	}

	public function testSimulateMatchWithCompletedFlagCompletesImmediately(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);
		$db = $this->mockDb();

		$home = $this->makeFullTeam(1);
		$guest = $this->makeFullTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 0);
		$match->isCompleted = TRUE;

		$sim = new Simulator($db, $ws);
		$sim->simulateMatch($match, 90);
		$this->assertTrue($match->isCompleted);
		// minute should not have advanced since it was already completed
		$this->assertSame(0, $match->minute);
	}

	public function testSimulateMatchNotifiesOnBeforeMatchStarts(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);
		$db = $this->mockDb();

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 0);

		$observer = $this->createMock(\ISimulatorObserver::class);
		$observer->expects($this->once())->method('onBeforeMatchStarts');

		$sim = new Simulator($db, $ws);
		$sim->attachObserver($observer);
		$sim->simulateMatch($match, 90);
	}

	public function testSimulateMatchNotifiesOnMatchCompleted(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);
		$db = $this->mockDb();

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 0);

		$observer = $this->createMock(\ISimulatorObserver::class);
		$observer->expects($this->once())->method('onMatchCompleted');

		$sim = new Simulator($db, $ws);
		$sim->attachObserver($observer);
		$sim->simulateMatch($match, 90);
	}

	public function testSimulateMatchWithPlayersRunsSimulation(): void {
		$ws = $this->mockWebsoccer($this->simConfig());
		\WebSoccer::setInstanceForTesting($ws);
		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);
		$db = $this->mockDb();

		$home = $this->makeFullTeam(1);
		$guest = $this->makeFullTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 0);

		$sim = new Simulator($db, $ws);
		$sim->simulateMatch($match, 95);
		$this->assertTrue($match->isCompleted);
		$this->assertGreaterThan(0, $match->minute);
	}
}
