<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for RandomEventsDataService.
 */
final class RandomEventsDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'randomevents_interval_days' => 7,
			'projectname' => 'WS',
			'no_transactions_for_teams_without_user' => 0,
		]);
	}

	public function testCreateEventIfRequiredReturnsWhenIntervalDisabled(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'randomevents_interval_days' => 0]);
		$db = $this->mockDb();
		$db->expects($this->never())->method('querySelect');
		RandomEventsDataService::createEventIfRequired($ws, $db, 5);
	}

	public function testCreateEventIfRequiredReturnsWhenUserHasNoClub(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryInsert');
		RandomEventsDataService::createEventIfRequired($this->ws, $db, 5);
	}

	public function testCreateEventIfRequiredReturnsWhenUserRegisteredRecently(): void {
		$now = time();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => 5]]),              // clubs
			$this->dbResult([['datum_anmeldung' => $now]]) // user registered today
		);
		$db->expects($this->never())->method('queryInsert');
		RandomEventsDataService::createEventIfRequired($this->ws, $db, 5);
	}

	public function testCreateEventIfRequiredReturnsWhenLatestEventIsRecent(): void {
		$now = time();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => 5]]),                          // clubs
			$this->dbResult([['datum_anmeldung' => $now - 48 * 3600]]), // user old enough
			$this->dbResult([['occurrence_date' => $now]])           // latest event recent
		);
		$db->expects($this->never())->method('queryInsert');
		RandomEventsDataService::createEventIfRequired($this->ws, $db, 5);
	}

	public function testCreateEventIfRequiredDoesNothingWhenNoEventsAvailable(): void {
		$now = time();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => 5]]),                            // clubs
			$this->dbResult([['datum_anmeldung' => $now - 48 * 3600]]),// user old enough
			$this->dbResult([]),                                       // no latest event
			$this->dbResult([])                                        // no events available
		);
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryDelete');
		RandomEventsDataService::createEventIfRequired($this->ws, $db, 5);
	}

	public function testCreateEventIfRequiredExecutesMoneyEventAndLogsOccurrence(): void {
		$now = time();
		$event = [
			'id' => 1, 'weight' => 1, 'effect' => 'money',
			'effect_money_amount' => 100, 'message' => 'You received money',
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => 5]]),                            // clubs
			$this->dbResult([['datum_anmeldung' => $now - 48 * 3600]]),// user old enough
			$this->dbResult([]),                                       // no latest event
			$this->dbResult([$event])                                  // events available
		);
		// team summary for BankAccountDataService::creditAmount
		$db->method('queryCachedSelect')->willReturn([['team_id' => 5, 'team_budget' => 1000, 'user_id' => 10]]);

		$inserts = [];
		$db->method('queryInsert')->willReturnCallback(function ($cols) use (&$inserts) {
			$inserts[] = $cols;
		});
		$db->method('queryUpdate');
		$db->expects($this->never())->method('queryDelete');

		RandomEventsDataService::createEventIfRequired($this->ws, $db, 5);

		// Find the occurrence-log insert (identified by the event_id column).
		$occurrence = null;
		foreach ($inserts as $cols) {
			if (isset($cols['event_id'])) {
				$occurrence = $cols;
			}
		}
		$this->assertNotNull($occurrence);
		$this->assertSame(1, $occurrence['event_id']);
		$this->assertSame(5, $occurrence['team_id']);
		$this->assertSame(5, $occurrence['user_id']);
	}
}
