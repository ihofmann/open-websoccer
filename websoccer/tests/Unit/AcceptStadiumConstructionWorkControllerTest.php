<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AcceptStadiumConstructionWorkController.
 */
final class AcceptStadiumConstructionWorkControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($selectRowsByTable) {
				foreach ($selectRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		return $db;
	}

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId($clubId);
		return $user;
	}

	private function makeWebsoccer(array $config): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getNowAsTimestamp')->willReturn(1000000);
		return $ws;
	}

	public function testExecuteActionThrowsWhenNoConstructionIsDue(): void {
		$ws = $this->makeWebsoccer(['db_prefix' => 'ws', 'stadium_construction_delay' => 7]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptStadiumConstructionWorkController(
			$this->mockI18n(['stadium_acceptconstruction_err_nonedue' => 'none due']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('none due');
		$controller->executeAction([]);
	}

	public function testExecuteActionThrowsWhenConstructionDeadlineIsInFuture(): void {
		$db = $this->makeDb(['_stadium_construction' => [['id' => 1, 'deadline' => 999999999, 'builder_reliability' => 100]]]);
		$ws = $this->makeWebsoccer(['db_prefix' => 'ws', 'stadium_construction_delay' => 7]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptStadiumConstructionWorkController(
			$this->mockI18n(['stadium_acceptconstruction_err_nonedue' => 'none due']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('none due');
		$controller->executeAction([]);
	}

	public function testExecuteActionCompletesConstructionAndUpdatesStadium(): void {
		$construction = ['id' => 1, 'deadline' => 1, 'builder_reliability' => 100,
			'p_steh' => 100, 'p_sitz' => 50, 'p_haupt_steh' => 20, 'p_haupt_sitz' => 10, 'p_vip' => 5];
		$stadium = ['stadium_id' => 9, 'places_stands' => 1000, 'places_seats' => 500,
			'places_stands_grand' => 200, 'places_seats_grand' => 100, 'places_vip' => 50];
		$db = $this->makeDb(['_stadium_construction' => [$construction], '_stadion' => [$stadium]]);

		$updates = [];
		$deletes = [];
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable) use (&$updates) {
			$updates[] = $fromTable;
			return null;
		});
		$db->method('queryDelete')->willReturnCallback(function ($fromTable) use (&$deletes) {
			$deletes[] = $fromTable;
			return null;
		});

		$ws = $this->makeWebsoccer(['db_prefix' => 'ws', 'stadium_construction_delay' => 7]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptStadiumConstructionWorkController($this->mockI18n([
			'stadium_acceptconstruction_completed_title' => 'done',
			'stadium_acceptconstruction_completed_details' => 'details',
		]), $ws, $db);

		$this->assertNull($controller->executeAction([]));
		$this->assertContains('ws_stadion', $updates);
		$this->assertContains('ws_stadium_construction', $deletes);
	}

	public function testExecuteActionPostponesDeadlineWhenNotCompleted(): void {
		$construction = ['id' => 1, 'deadline' => 1, 'builder_reliability' => 0,
			'p_steh' => 100, 'p_sitz' => 50, 'p_haupt_steh' => 20, 'p_haupt_sitz' => 10, 'p_vip' => 5];
		$db = $this->makeDb(['_stadium_construction' => [$construction]]);

		$postponed = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable) use (&$postponed) {
			if (strpos($fromTable, '_stadium_construction') !== false) {
				$postponed = $columns;
			}
			return null;
		});

		$ws = $this->makeWebsoccer(['db_prefix' => 'ws', 'stadium_construction_delay' => 7]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptStadiumConstructionWorkController($this->mockI18n([
			'stadium_acceptconstruction_notcompleted_title' => 'warn',
			'stadium_acceptconstruction_notcompleted_details' => 'wdetails',
		]), $ws, $db);

		$this->assertNull($controller->executeAction([]));
		$this->assertSame(1000000 + 7 * 24 * 3600, $postponed['deadline']);
	}
}
