<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchSimulationExecutor.
 */
final class YouthMatchSimulationExecutorTest extends TestCaseBase {
	public function testSimulateOpenYouthMatchesReturnsEarlyWhenFeatureDisabled(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'youth_enabled' => FALSE,
		]);

		// Should return immediately without querying matches
		YouthMatchSimulationExecutor::simulateOpenYouthMatches($ws, $db, 10);
		$this->assertTrue(TRUE); // just verify no exception
	}

	public function testSimulateOpenYouthMatchesWithNoMatchesDoesNothing(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->method('queryInsert')->willReturn(null);
		$db->method('queryUpdate')->willReturn(null);
		$db->method('queryDelete')->willReturn(null);

		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'youth_enabled' => TRUE,
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
			'sim_weight_strength' => 50,
			'sim_weight_strengthTech' => 20,
			'sim_weight_strengthStamina' => 10,
			'sim_weight_strengthFreshness' => 10,
			'sim_weight_strengthSatisfaction' => 10,
			'sim_home_field_advantage' => 5,
			'sim_createformation_strength' => 60,
			'sim_strength_reduction_wrongposition' => 10,
			'sim_strength_reduction_secondary' => 5,
			'supported_languages' => 'en',
		]);
		\WebSoccer::setInstanceForTesting($ws);

		// No matches to simulate -> should complete without error
		YouthMatchSimulationExecutor::simulateOpenYouthMatches($ws, $db, 10);
		$this->assertTrue(TRUE);
	}
}
