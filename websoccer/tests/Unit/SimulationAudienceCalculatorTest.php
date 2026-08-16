<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SimulationAudienceCalculator.
 */
final class SimulationAudienceCalculatorTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	/**
	 * Calls the private computeRate() method via reflection.
	 */
	private function callComputeRate(float $avgPrice, float $avgSales, float $actualPrice, int $fanpopularity, bool $isAttractive, float $maintenanceInfluence): float {
		$method = new ReflectionMethod('SimulationAudienceCalculator', 'computeRate');
		return $method->invoke(null, $avgPrice, $avgSales, $actualPrice, $fanpopularity, $isAttractive, $maintenanceInfluence);
	}

	public function testComputeRateReturnsValueBetweenZeroAndOne(): void {
		$rate = $this->callComputeRate(10, 60, 10, 50, FALSE, 0);
		$this->assertGreaterThanOrEqual(0.0, $rate);
		$this->assertLessThanOrEqual(1.0, $rate);
	}

	public function testComputeRateHigherForLowerPrice(): void {
		$rateHighPrice = $this->callComputeRate(10, 60, 20, 50, FALSE, 0);
		$rateLowPrice = $this->callComputeRate(10, 60, 5, 50, FALSE, 0);
		$this->assertGreaterThan($rateHighPrice, $rateLowPrice);
	}

	public function testComputeRateHigherForAttractiveMatch(): void {
		$rateNormal = $this->callComputeRate(10, 60, 10, 50, FALSE, 0);
		$rateAttractive = $this->callComputeRate(10, 60, 10, 50, TRUE, 0);
		$this->assertGreaterThan($rateNormal, $rateAttractive);
	}

	public function testComputeRateHigherForBetterMaintenance(): void {
		$rateNoMaint = $this->callComputeRate(10, 60, 10, 50, FALSE, 0);
		$rateWithMaint = $this->callComputeRate(10, 60, 10, 50, FALSE, 5);
		$this->assertGreaterThan($rateNoMaint, $rateWithMaint);
	}

	public function testComputeRateHigherForGreaterFanPopularity(): void {
		$rateLowPop = $this->callComputeRate(10, 60, 10, 20, FALSE, 0);
		$rateHighPop = $this->callComputeRate(10, 60, 10, 80, FALSE, 0);
		$this->assertGreaterThan($rateLowPop, $rateHighPop);
	}

	public function testComputeRateClampedToMaximum(): void {
		// Very low price, high popularity, attractive, good maintenance
		$rate = $this->callComputeRate(10, 100, 1, 100, TRUE, 100);
		$this->assertLessThanOrEqual(1.0, $rate);
	}

	public function testComputeRateClampedToMinimum(): void {
		// Very high price, low popularity
		$rate = $this->callComputeRate(10, 0, 1000, 0, FALSE, -100);
		$this->assertGreaterThanOrEqual(0.0, $rate);
	}

	public function testComputeAndSaveAudienceReturnsEarlyWhenNoHomeInfo(): void {
		$db = $this->createMock(\DbConnection::class);
		// getHomeInfo returns empty -> false -> method returns early
		$db->method('querySelect')->willReturn(new MockDbResult([]));

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 0);
		$match->type = 'Ligaspiel';

		// Should not throw any exceptions
		SimulationAudienceCalculator::computeAndSaveAudience($ws, $db, $match);
		$this->assertFalse($match->isSoldOut);
	}

	public function testComputeAndSaveAudienceSetsSoldOutWhenAllRatesFull(): void {
		// Build homeInfo with zero-capacity stadium so all rates trivially max out
		$homeInfo = [
			'stadium_id' => 1,
			'places_stands' => 0, 'places_seats' => 0,
			'places_stands_grand' => 0, 'places_seats_grand' => 0, 'places_vip' => 0,
			'level_pitch' => 5, 'level_videowall' => 5,
			'level_seatsquality' => 5, 'level_vipquality' => 5,
			'maintenance_pitch' => 10, 'maintenance_videowall' => 10,
			'maintenance_seatsquality' => 10, 'maintenance_vipquality' => 10,
			'popularity' => 100,
			'price_stands' => 1, 'price_seats' => 1,
			'price_stands_grand' => 1, 'price_seats_grand' => 1, 'price_vip' => 1,
			'avg_sales_stands' => 100, 'avg_sales_seats' => 100,
			'avg_sales_stands_grand' => 100, 'avg_sales_seats_grand' => 100, 'avg_sales_vip' => 100,
			'avg_price_stands' => 10, 'avg_price_seats' => 10, 'avg_price_vip' => 10,
		];

		// querySelect calls in order:
		// 1. getHomeInfo (returns homeInfo)
		// 2-3. League match points queries (for attractiveness check)
		// Then BankAccountDataService::creditAmount does more querySelect calls
		$callIndex = 0;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function () use (&$callIndex, $homeInfo) {
			$callIndex++;
			if ($callIndex === 1) {
				return new MockDbResult([$homeInfo]);
			}
			// Return team points for attractiveness check and other queries
			return new MockDbResult([['sa_punkte' => 10, 'user_id' => 1]]);
		});
		$db->method('queryUpdate')->willReturn(null);
		$db->method('queryInsert')->willReturn(null);

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'supported_languages' => 'en',
			'stadium_videowall_effect' => 5,
			'stadium_seatsquality_effect' => 5,
			'stadium_vipquality_effect' => 5,
			'stadium_pitch_effect' => 2,
			'stadium_maintenanceinterval_pitch' => 10,
			'stadium_maintenanceinterval_videowall' => 10,
			'stadium_maintenanceinterval_seatsquality' => 10,
			'stadium_maintenanceinterval_vipquality' => 10,
		]);
		$I18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($I18n);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		// Add a player so weakenPlayersDueToGrassQuality has something to iterate
		$p = new SimulationPlayer(1, $home, PLAYER_POSITION_DEFENCE, 'IV', 3.0, 25, 80, 70, 60, 90, 85);
		$home->positionsAndPlayers[PLAYER_POSITION_DEFENCE][] = $p;
		$match = new SimulationMatch(1, $home, $guest, 0);
		$match->type = 'Pokalspiel';

		SimulationAudienceCalculator::computeAndSaveAudience($ws, $db, $match);
		$this->assertTrue($match->isSoldOut);
	}
}
