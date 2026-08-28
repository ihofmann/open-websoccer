<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for ExecuteTransfersJob.
 */
final class ExecuteTransfersJobTest extends TestCaseBase {
	use JobTestHelper;

	protected function setUp(): void {
		parent::setUp();
	}

	public function testExecuteWithNoOpenTransfersDoesNotCallQueryUpdate(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) {
			if (strpos($fromTable, '_jobs') !== false) {
				return new MockDbResult([$this->jobRow('extransf')]);
			}
			return new MockDbResult([]);
		});
		$businessUpdates = 0;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable) use (&$businessUpdates) {
			if (strpos($fromTable, '_jobs') === false) {
				$businessUpdates++;
			}
		});
		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new ExecuteTransfersJob($ws, $db, $i18n, 'extransf', false);
		$job->execute();
		$this->assertSame(0, $businessUpdates);
	}

	public function testExecuteDelegatesToTransfermarketDataService(): void {
		// Verify the job calls the service by checking querySelect is invoked
		// with the player transfer table.
		$selectCalled = false;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use (&$selectCalled) {
			if (strpos($fromTable, '_jobs') !== false) {
				return new MockDbResult([$this->jobRow('extransf')]);
			}
			if (strpos($fromTable, '_spieler') !== false) {
				$selectCalled = true;
			}
			return new MockDbResult([]);
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new ExecuteTransfersJob($ws, $db, $i18n, 'extransf', false);
		$job->execute();

		$this->assertTrue($selectCalled);
	}

	public function testExecuteWithEndedAuctionAndNoBidAttemptsExtendDuration(): void {
		// Player with ended auction but no bid triggers extendDuration(), which
		// extends the auction end date (queryUpdate on the players table).
		$playerRow = [
			'player_id' => 1, 'transfer_start' => 100, 'transfer_end' => 200,
			'first_name' => 'John', 'last_name' => 'Doe', 'pseudonym' => '',
			'team_id' => null, 'team_name' => null, 'team_user_id' => null,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($playerRow) {
			if (strpos($fromTable, '_jobs') !== false) {
				return new MockDbResult([$this->jobRow('extransf')]);
			}
			if (strpos($fromTable, '_spieler AS P') !== false) {
				return new MockDbResult([$playerRow]);
			}
			// getHighestBidForPlayer query returns empty (no bid).
			return new MockDbResult([]);
		});

		$extended = false;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$extended) {
			if (strpos($fromTable, '_spieler') !== false && isset($columns['transfer_ende'])) {
				$extended = true;
			}
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new ExecuteTransfersJob($ws, $db, $i18n, 'extransf', false);
		$job->execute();

		$this->assertTrue($extended, 'extendDuration() should update transfer_ende on the players table');
	}
}
