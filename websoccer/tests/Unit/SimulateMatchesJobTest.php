<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;
use OpenWebSoccer\Tests\MockDbResult;

if (!defined('JOBS_CONFIG_FILE')) {
	define('JOBS_CONFIG_FILE', sys_get_temp_dir() . '/ows_jobs_test.xml');
}

/**
 * Unit tests for SimulateMatchesJob.
 */
final class SimulateMatchesJobTest extends TestCaseBase {
	use JobTestHelper;

	private function simConfig(): array {
		return array_merge($this->jobConfig(), [
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
			'sim_simulation_observers' => '',
			'sim_simulator_observers' => '',
			// 0 means no matches queried and no youth matches.
			'sim_max_matches_per_run' => 0,
		]);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->writeJobConfig(0);
	}

	protected function tearDown(): void {
		@file_put_contents(JOBS_CONFIG_FILE, $this->jobXml(0));
		parent::tearDown();
	}

	public function testExecuteWithNoOpenMatchesDoesNotSimulate(): void {
		$db = $this->createMock(\DbConnection::class);
		// All selects return empty.
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->expects($this->never())->method('queryUpdate');

		$ws = $this->mockWebsoccer($this->simConfig());
		$i18n = $this->mockI18n();

		$job = new SimulateMatchesJob($ws, $db, $i18n, 'sim', false);
		$job->execute();
	}

	public function testExecuteDelegatesToMatchSimulationExecutor(): void {
		// Verify the job triggers the match query by checking querySelect
		// is called with the _spiel table.
		$selectCalled = false;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use (&$selectCalled) {
			if (strpos($fromTable, '_spiel') !== false) {
				$selectCalled = true;
			}
			return new MockDbResult([]);
		});

		$ws = $this->mockWebsoccer($this->simConfig());
		$i18n = $this->mockI18n();

		$job = new SimulateMatchesJob($ws, $db, $i18n, 'sim', false);
		$job->execute();

		$this->assertTrue($selectCalled);
	}
}
