<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for StadiumsDataService.
 */
final class StadiumsDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'stadium_cost_standing' => 10,
			'stadium_cost_seats' => 20,
			'stadium_cost_standing_grand' => 30,
			'stadium_cost_seats_grand' => 40,
			'stadium_cost_vip' => 100,
			'stadium_pitch_price' => 1000,
			'stadium_videowall_price' => 500,
			'stadium_seatsquality_price' => 5,
			'stadium_vipquality_price' => 50,
			'stadium_maintenance_priceincrease_per_level' => 10,
		]);
	}

	public function testGetStadiumByTeamIdReturnsNullForZeroClubId(): void {
		$db = $this->mockDb();
		$this->assertNull(StadiumsDataService::getStadiumByTeamId($this->ws, $db, 0));
	}

	public function testGetStadiumByTeamIdReturnsRow(): void {
		$row = ['stadium_id' => 1, 'name' => 'Arena', 'places_stands' => 1000];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, StadiumsDataService::getStadiumByTeamId($this->ws, $db, 5));
	}

	public function testGetStadiumByTeamIdReturnsFalseWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(StadiumsDataService::getStadiumByTeamId($this->ws, $db, 5));
	}

	public function testGetBuilderOffersForExtensionReturnsEmptyWhenNoNewSeats(): void {
		$db = $this->mockDb();
		$this->assertSame([], StadiumsDataService::getBuilderOffersForExtension($this->ws, $db, 5));
	}

	public function testGetBuilderOffersForExtensionComputesOffer(): void {
		$stadiumRow = [
			'places_stands' => 1000,
			'places_seats' => 500,
			'places_stands_grand' => 200,
			'places_seats_grand' => 100,
			'places_vip' => 50,
		];
		$builderRow = [
			'id' => 1,
			'name' => 'Builder1',
			'picture' => 'b1.jpg',
			'premiumfee' => 10,
			'construction_time_days_min' => 2,
			'construction_time_days' => 1,
			'cost_per_seat' => 5,
			'fixedcosts' => 1000,
			'reliability' => 90,
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$stadiumRow]),
			$this->dbResult([$builderRow])
		);
		$offers = StadiumsDataService::getBuilderOffersForExtension($this->ws, $db, 5, 1000);
		$this->assertArrayHasKey(1, $offers);
		$offer = $offers[1];
		$this->assertSame(1, $offer['builder_id']);
		$this->assertSame('Builder1', $offer['builder_name']);
		$this->assertSame(2, $offer['deadline_days']);
		// 1000 * (10 standing + 5 per seat) = 15000; + fixed 1000 = 16000
		$this->assertSame(15000, $offer['costsSideStanding']);
		$this->assertSame(0, $offer['costsSideSeats']);
		$this->assertSame(16000, $offer['totalCosts']);
	}

	public function testGetBuilderOffersForExtensionUsesMaxConstructionTime(): void {
		$stadiumRow = [
			'places_stands' => 0, 'places_seats' => 0, 'places_stands_grand' => 0,
			'places_seats_grand' => 0, 'places_vip' => 0,
		];
		// 6000 new seats -> ceil(6000/5000) = 2 days; min is 10 -> max(10,2)=10
		$builderRow = [
			'id' => 2, 'name' => 'B2', 'picture' => 'p', 'premiumfee' => 0,
			'construction_time_days_min' => 10, 'construction_time_days' => 1,
			'cost_per_seat' => 0, 'fixedcosts' => 500, 'reliability' => 50,
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$stadiumRow]),
			$this->dbResult([$builderRow])
		);
		$offers = StadiumsDataService::getBuilderOffersForExtension($this->ws, $db, 5, 6000);
		$this->assertSame(10, $offers[2]['deadline_days']);
	}

	public function testGetCurrentConstructionOrderOfTeamReturnsOrder(): void {
		$row = ['id' => 1, 'builder_name' => 'B1', 'builder_reliability' => 90];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, StadiumsDataService::getCurrentConstructionOrderOfTeam($this->ws, $db, 5));
	}

	public function testGetCurrentConstructionOrderOfTeamReturnsNullWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertNull(StadiumsDataService::getCurrentConstructionOrderOfTeam($this->ws, $db, 5));
	}

	public function testGetDueConstructionOrdersReturnsList(): void {
		$rows = [
			['id' => 1, 'user_id' => 10, 'builder_reliability' => 80],
			['id' => 2, 'user_id' => 20, 'builder_reliability' => 90],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertSame($rows, StadiumsDataService::getDueConstructionOrders($this->ws, $db));
	}

	public function testGetDueConstructionOrdersReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], StadiumsDataService::getDueConstructionOrders($this->ws, $db));
	}

	public function testComputeUpgradeCostsReturnsZeroAtMaxLevel(): void {
		$stadium = ['level_pitch' => 5];
		$this->assertSame(0, StadiumsDataService::computeUpgradeCosts($this->ws, 'pitch', $stadium));
	}

	public function testComputeUpgradeCostsReturnsZeroAboveMaxLevel(): void {
		$stadium = ['level_pitch' => 7];
		$this->assertSame(0, StadiumsDataService::computeUpgradeCosts($this->ws, 'pitch', $stadium));
	}

	public function testComputeUpgradeCostsForPitchLevel0(): void {
		// baseCost=1000, level=0 -> additionFactor=0 -> 1000
		$stadium = ['level_pitch' => 0];
		$this->assertEquals(1000, StadiumsDataService::computeUpgradeCosts($this->ws, 'pitch', $stadium));
	}

	public function testComputeUpgradeCostsForPitchLevel2(): void {
		// baseCost=1000, level=2, increase=10 -> factor=0.2 -> round(1000+200)=1200
		$stadium = ['level_pitch' => 2];
		$this->assertEquals(1200, StadiumsDataService::computeUpgradeCosts($this->ws, 'pitch', $stadium));
	}

	public function testComputeUpgradeCostsForSeatsQuality(): void {
		// baseCost = 5 * (100+50) = 750; level=1, increase=10 -> factor=0.1 -> round(750+75)=825
		$stadium = ['level_seatsquality' => 1, 'places_seats' => 100, 'places_seats_grand' => 50];
		$this->assertEquals(825, StadiumsDataService::computeUpgradeCosts($this->ws, 'seatsquality', $stadium));
	}

	public function testComputeUpgradeCostsForVipQuality(): void {
		// baseCost = 50 * 20 = 1000; level=2, increase=10 -> factor=0.2 -> round(1000+200)=1200
		$stadium = ['level_vipquality' => 2, 'places_vip' => 20];
		$this->assertEquals(1200, StadiumsDataService::computeUpgradeCosts($this->ws, 'vipquality', $stadium));
	}
}
