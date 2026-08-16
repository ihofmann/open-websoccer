<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TrainingDataService.
 */
final class TrainingDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
	}

	public function testCountTrainersReturnsHitsFromResult(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 7]]));
		$this->assertSame(7, TrainingDataService::countTrainers($this->ws, $db));
	}

	public function testGetTrainersReturnsListOfRows(): void {
		$rows = [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertSame($rows, TrainingDataService::getTrainers($this->ws, $db, 0, 10));
	}

	public function testGetTrainersReturnsEmptyArrayWhenNoRows(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TrainingDataService::getTrainers($this->ws, $db, 0, 10));
	}

	public function testGetTrainerByIdReturnsRow(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 5, 'name' => 'X']]));
		$this->assertSame(['id' => 5, 'name' => 'X'], TrainingDataService::getTrainerById($this->ws, $db, 5));
	}

	public function testGetTrainerByIdReturnsFalseWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(TrainingDataService::getTrainerById($this->ws, $db, 999));
	}

	public function testCountRemainingTrainingUnitsReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 3]]));
		$this->assertSame(3, TrainingDataService::countRemainingTrainingUnits($this->ws, $db, 42));
	}

	public function testGetLatestTrainingExecutionTimeReturnsTimestamp(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['date_executed' => 1700000000]]));
		$this->assertSame(1700000000, TrainingDataService::getLatestTrainingExecutionTime($this->ws, $db, 42));
	}

	public function testGetLatestTrainingExecutionTimeReturnsZeroWhenNoUnit(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['date_executed' => 0]]));
		$this->assertSame(0, TrainingDataService::getLatestTrainingExecutionTime($this->ws, $db, 42));
	}

	public function testGetLatestTrainingExecutionTimeReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, TrainingDataService::getLatestTrainingExecutionTime($this->ws, $db, 42));
	}

	public function testGetValidTrainingUnitReturnsRow(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 8, 'trainer_id' => 2]]));
		$this->assertSame(['id' => 8, 'trainer_id' => 2], TrainingDataService::getValidTrainingUnit($this->ws, $db, 42));
	}

	public function testGetValidTrainingUnitReturnsFalseWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(TrainingDataService::getValidTrainingUnit($this->ws, $db, 42));
	}

	public function testGetTrainingUnitByIdReturnsRow(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 9, 'team_id' => 42]]));
		$this->assertSame(['id' => 9, 'team_id' => 42], TrainingDataService::getTrainingUnitById($this->ws, $db, 42, 9));
	}

	public function testGetTrainingUnitByIdReturnsFalseWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(TrainingDataService::getTrainingUnitById($this->ws, $db, 42, 9));
	}
}
