<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DirectTransferRejectController.
 */
final class DirectTransferRejectControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = [], array $cachedRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
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
		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($cachedRowsByTable) {
				foreach ($cachedRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $rows;
					}
				}
				return [];
			}
		);
		return $db;
	}

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager']);
		$user->setClubId($clubId);
		return $user;
	}

	private function baseConfig(): array {
		return ['transferoffers_enabled' => true, 'db_prefix' => 'ws',
			'players_aging' => 'age', 'transfermarket_computed_marketvalue' => false];
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['transferoffers_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferRejectController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5, 'comment' => 'no', 'allow_alternative' => 0]));
	}

	public function testExecuteActionThrowsWhenOfferNotFound(): void {
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferRejectController(
			$this->mockI18n(['transferoffers_offer_cancellation_notfound' => 'not found']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not found');
		$controller->executeAction(['id' => 99, 'comment' => 'no', 'allow_alternative' => 0]);
	}

	public function testExecuteActionRejectsOfferAndReturnsNull(): void {
		$db = $this->makeDb(
			['_transfer_offer' => [['id' => 5, 'player_id' => 9, 'sender_user_id' => 2]]],
			['_spieler AS P' => [['player_id' => 9, 'player_pseudonym' => '', 'player_firstname' => 'A', 'player_lastname' => 'B',
				'player_position' => 'Torwart', 'player_nationality' => 'Deutschland', 'matches_info' => '0;0', 'player_marketvalue' => 1000]]]
		);
		$updated = null;
		$inserted = false;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable) use (&$updated) {
			if (strpos($fromTable, '_transfer_offer') !== false) {
				$updated = $columns;
			}
			return null;
		});
		$db->method('queryInsert')->willReturnCallback(function () use (&$inserted) {
			$inserted = true;
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferRejectController(
			$this->mockI18n(['transferoffers_offer_reject_success' => 'rejected']), $ws, $db);

		$this->assertNull($controller->executeAction(['id' => 5, 'comment' => 'too low', 'allow_alternative' => 1]));
		$this->assertSame('too low', $updated['rejected_message']);
		$this->assertSame(1, $updated['rejected_allow_alternative']);
		$this->assertTrue($inserted);
	}
}
