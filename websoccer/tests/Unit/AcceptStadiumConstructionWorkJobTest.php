<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;
use OpenWebSoccer\Tests\MockDbResult;

if (!defined('JOBS_CONFIG_FILE')) {
	define('JOBS_CONFIG_FILE', sys_get_temp_dir() . '/ows_jobs_test.xml');
}

/**
 * Unit tests for AcceptStadiumConstructionWorkJob.
 */
final class AcceptStadiumConstructionWorkJobTest extends TestCaseBase {
	use JobTestHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->writeJobConfig(0);
	}

	protected function tearDown(): void {
		@file_put_contents(JOBS_CONFIG_FILE, $this->jobXml(0));
		parent::tearDown();
	}

	/**
	 * Creates a DbConnection mock with a callback that routes querySelect
	 * results based on the from-table name, so different service queries
	 * get different data.
	 */
	private function makeDb(callable $selectCallback): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback($selectCallback);
		$db->method('queryUpdate');
		$db->method('queryDelete');
		$db->method('queryInsert');
		return $db;
	}

	public function testExecuteWithNoConstructionsAndNoCampsDoesNotModifyDb(): void {
		$db = $this->makeDb(function () {
			return new MockDbResult([]);
		});
		$db->expects($this->never())->method('queryUpdate');
		$db->expects($this->never())->method('queryDelete');
		$db->expects($this->never())->method('queryInsert');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();
	}

	public function testExecuteWithCompletedConstructionUpdatesStadiumAndDeletesOrder(): void {
		// builder_reliability=100 => always completed.
		$construction = [
			'id' => 10, 'team_id' => 5, 'user_id' => 0,
			'builder_reliability' => 100,
			'p_steh' => 100, 'p_sitz' => 200, 'p_haupt_steh' => 50,
			'p_haupt_sitz' => 60, 'p_vip' => 10,
		];
		$stadium = [
			'stadium_id' => 3, 'name' => 'Arena', 'picture' => '',
			'places_stands' => 1000, 'places_seats' => 2000,
			'places_stands_grand' => 500, 'places_seats_grand' => 600,
			'places_vip' => 100, 'level_pitch' => 0, 'level_videowall' => 0,
			'level_seatsquality' => 0, 'level_vipquality' => 0,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($construction, $stadium) {
			// getDueConstructionOrders: fromTable contains _stadium_construction
			if (strpos($fromTable, '_stadium_construction') !== false) {
				return new MockDbResult([$construction]);
			}
			// getStadiumByTeamId: fromTable contains _stadion
			if (strpos($fromTable, '_stadion') !== false) {
				return new MockDbResult([$stadium]);
			}
			// checkTrainingCamps: fromTable contains _trainingslager
			return new MockDbResult([]);
		});

		// Completed: queryUpdate (stadium) + queryDelete (construction order).
		// No notification because user_id=0.
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->once())->method('queryDelete');
		$db->expects($this->never())->method('queryInsert');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();
	}

	public function testExecuteWithCompletedConstructionAndUserSendsNotification(): void {
		$construction = [
			'id' => 10, 'team_id' => 5, 'user_id' => 3,
			'builder_reliability' => 100,
			'p_steh' => 100, 'p_sitz' => 200, 'p_haupt_steh' => 50,
			'p_haupt_sitz' => 60, 'p_vip' => 10,
		];
		$stadium = [
			'stadium_id' => 3, 'name' => 'Arena', 'picture' => '',
			'places_stands' => 1000, 'places_seats' => 2000,
			'places_stands_grand' => 500, 'places_seats_grand' => 600,
			'places_vip' => 100, 'level_pitch' => 0, 'level_videowall' => 0,
			'level_seatsquality' => 0, 'level_vipquality' => 0,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($construction, $stadium) {
			if (strpos($fromTable, '_stadium_construction') !== false) {
				return new MockDbResult([$construction]);
			}
			if (strpos($fromTable, '_stadion') !== false) {
				return new MockDbResult([$stadium]);
			}
			return new MockDbResult([]);
		});

		// Completed + user_id set: queryUpdate (stadium) + queryDelete (order)
		// + queryInsert (notification).
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->once())->method('queryDelete');
		$db->expects($this->once())->method('queryInsert');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();
	}

	public function testExecuteWithNotCompletedConstructionPostponesDeadline(): void {
		// builder_reliability=0 => always not completed.
		$construction = [
			'id' => 10, 'team_id' => 5, 'user_id' => 0,
			'builder_reliability' => 0,
			'p_steh' => 100, 'p_sitz' => 200, 'p_haupt_steh' => 50,
			'p_haupt_sitz' => 60, 'p_vip' => 10,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($construction) {
			if (strpos($fromTable, '_stadium_construction') !== false) {
				return new MockDbResult([$construction]);
			}
			return new MockDbResult([]);
		});

		// Not completed: queryUpdate (postpone deadline) only.
		// No queryDelete (order stays), no queryInsert (no notification, user_id=0).
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->never())->method('queryDelete');
		$db->expects($this->never())->method('queryInsert');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();
	}

	public function testExecuteWithNotCompletedConstructionAndUserSendsNotification(): void {
		$construction = [
			'id' => 10, 'team_id' => 5, 'user_id' => 3,
			'builder_reliability' => 0,
			'p_steh' => 100, 'p_sitz' => 200, 'p_haupt_steh' => 50,
			'p_haupt_sitz' => 60, 'p_vip' => 10,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($construction) {
			if (strpos($fromTable, '_stadium_construction') !== false) {
				return new MockDbResult([$construction]);
			}
			return new MockDbResult([]);
		});

		// Not completed + user_id: queryUpdate (postpone) + queryInsert (notification).
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->never())->method('queryDelete');
		$db->expects($this->once())->method('queryInsert');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();
	}

	public function testExecuteWithCompletedConstructionUpdatesStadiumColumnsCorrectly(): void {
		$construction = [
			'id' => 10, 'team_id' => 5, 'user_id' => 0,
			'builder_reliability' => 100,
			'p_steh' => 100, 'p_sitz' => 200, 'p_haupt_steh' => 50,
			'p_haupt_sitz' => 60, 'p_vip' => 10,
		];
		$stadium = [
			'stadium_id' => 3, 'name' => 'Arena', 'picture' => '',
			'places_stands' => 1000, 'places_seats' => 2000,
			'places_stands_grand' => 500, 'places_seats_grand' => 600,
			'places_vip' => 100, 'level_pitch' => 0, 'level_videowall' => 0,
			'level_seatsquality' => 0, 'level_vipquality' => 0,
		];

		$capturedColumns = null;
		$capturedTable = null;

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($construction, $stadium) {
			if (strpos($fromTable, '_stadium_construction') !== false) {
				return new MockDbResult([$construction]);
			}
			if (strpos($fromTable, '_stadion') !== false) {
				return new MockDbResult([$stadium]);
			}
			return new MockDbResult([]);
		});
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable) use (&$capturedColumns, &$capturedTable) {
			// Capture the stadium update call (not the training camp).
			if (strpos($fromTable, '_stadion') !== false) {
				$capturedColumns = $columns;
				$capturedTable = $fromTable;
			}
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();

		// Verify stadium column sums.
		$this->assertNotNull($capturedColumns);
		$this->assertSame(1100, $capturedColumns['p_steh']);  // 1000 + 100
		$this->assertSame(2200, $capturedColumns['p_sitz']);  // 2000 + 200
		$this->assertSame(550, $capturedColumns['p_haupt_steh']);  // 500 + 50
		$this->assertSame(660, $capturedColumns['p_haupt_sitz']);  // 600 + 60
		$this->assertSame(110, $capturedColumns['p_vip']);  // 100 + 10
	}

	public function testExecuteQueriesTrainingCampsBookings(): void {
		// Verify the job also queries training camp bookings.
		$campsQueryCalled = false;

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use (&$campsQueryCalled) {
			if (strpos($fromTable, '_trainingslager_belegung') !== false) {
				$campsQueryCalled = true;
			}
			return new MockDbResult([]);
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AcceptStadiumConstructionWorkJob($ws, $db, $i18n, 'stadium', false);
		$job->execute();

		$this->assertTrue($campsQueryCalled);
	}
}
