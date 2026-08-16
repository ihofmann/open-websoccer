<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TrainingcampsDataService.
 */
final class TrainingcampsDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'players_aging' => 'none']);
	}

	public function testGetCampsReturnsListOrderedByName(): void {
		$rows = [
			['id' => 1, 'name' => 'Camp A', 'country' => 'DE'],
			['id' => 2, 'name' => 'Camp B', 'country' => 'AT'],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertSame($rows, TrainingcampsDataService::getCamps($this->ws, $db));
	}

	public function testGetCampsReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TrainingcampsDataService::getCamps($this->ws, $db));
	}

	public function testGetCampBookingsByTeamReturnsList(): void {
		$rows = [
			['id' => 1, 'date_start' => 100, 'date_end' => 200, 'name' => 'Camp A'],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertSame($rows, TrainingcampsDataService::getCampBookingsByTeam($this->ws, $db, 7));
	}

	public function testGetCampBookingsByTeamReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TrainingcampsDataService::getCampBookingsByTeam($this->ws, $db, 7));
	}

	public function testGetCampByIdReturnsRow(): void {
		$row = ['id' => 3, 'name' => 'Camp C', 'country' => 'CH'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, TrainingcampsDataService::getCampById($this->ws, $db, 3));
	}

	public function testGetCampByIdReturnsFalseWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(TrainingcampsDataService::getCampById($this->ws, $db, 999));
	}

	public function testExecuteCampUpdatesNonInjuredPlayersAndDeletesBooking(): void {
		$players = [
			[
				'id' => 1, 'matches_injured' => 0, 'position' => 'Torwart',
				'strength' => 50, 'strength_technic' => 40, 'strength_stamina' => 60,
				'strength_freshness' => 70, 'strength_satisfaction' => 80,
			],
			// injured player must be skipped
			[
				'id' => 2, 'matches_injured' => 5, 'position' => 'Abwehr',
				'strength' => 50, 'strength_technic' => 40, 'strength_stamina' => 60,
				'strength_freshness' => 70, 'strength_satisfaction' => 80,
			],
		];
		$bookingInfo = [
			'id' => 42,
			'date_start' => 100,
			'date_end' => 100 + 2 * 24 * 3600, // 2 days duration
			'effect_strength' => 1,
			'effect_strength_technique' => 2,
			'effect_strength_stamina' => 3,
			'effect_strength_freshness' => 4,
			'effect_strength_satisfaction' => 5,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($players));
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->once())->method('queryDelete');

		TrainingcampsDataService::executeCamp($this->ws, $db, 7, $bookingInfo);
	}

	public function testExecuteCampWithNoPlayersOnlyDeletesBooking(): void {
		$bookingInfo = [
			'id' => 42, 'date_start' => 100, 'date_end' => 200,
			'effect_strength' => 1, 'effect_strength_technique' => 2,
			'effect_strength_stamina' => 3, 'effect_strength_freshness' => 4,
			'effect_strength_satisfaction' => 5,
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryUpdate');
		$db->expects($this->once())->method('queryDelete');

		TrainingcampsDataService::executeCamp($this->ws, $db, 7, $bookingInfo);
	}

	public function testExecuteCampClampsStrengthToMaximum(): void {
		// strength already near 100; effect * duration would exceed -> clamped to 100
		$players = [
			[
				'id' => 1, 'matches_injured' => 0, 'position' => 'Torwart',
				'strength' => 99, 'strength_technic' => 99, 'strength_stamina' => 99,
				'strength_freshness' => 99, 'strength_satisfaction' => 99,
			],
		];
		$bookingInfo = [
			'id' => 1, 'date_start' => 0, 'date_end' => 10 * 24 * 3600, // 10 days
			'effect_strength' => 10, 'effect_strength_technique' => 10,
			'effect_strength_stamina' => 10, 'effect_strength_freshness' => 10,
			'effect_strength_satisfaction' => 10,
		];
		$capturedColumns = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($players));
		$db->method('queryUpdate')->willReturnCallback(function ($columns) use (&$capturedColumns) {
			$capturedColumns = $columns;
		});
		$db->method('queryDelete');

		TrainingcampsDataService::executeCamp($this->ws, $db, 7, $bookingInfo);
		$this->assertNotNull($capturedColumns);
		$this->assertSame(100, $capturedColumns['w_staerke']);
		$this->assertSame(100, $capturedColumns['w_technik']);
		$this->assertSame(100, $capturedColumns['w_zufriedenheit']);
	}
}
