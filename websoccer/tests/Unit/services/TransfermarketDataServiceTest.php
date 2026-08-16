<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TransfermarketDataService.
 */
final class TransfermarketDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'transfermarket_duration_days' => 3,
		]);
	}

	public function testGetHighestBidForPlayerReturnsBid(): void {
		$row = ['bid_id' => 1, 'amount' => 1000, 'hand_money' => 50, 'contract_matches' => 10,
			'contract_salary' => 100, 'contract_goalbonus' => 200, 'date' => 500,
			'team_id' => 7, 'team_name' => 'FC', 'user_id' => 5, 'user_name' => 'u'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, TransfermarketDataService::getHighestBidForPlayer($this->ws, $db, 1, 100, 200));
	}

	public function testGetHighestBidForPlayerReturnsFalseWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(TransfermarketDataService::getHighestBidForPlayer($this->ws, $db, 1, 100, 200));
	}

	public function testGetCurrentBidsOfTeamKeepsOnlyFirstBidPerPlayer(): void {
		$rows = [
			['amount' => 1000, 'hand_money' => 0, 'contract_matches' => 10, 'contract_salary' => 100, 'contract_goalbonus' => 200, 'date' => 500, 'ishighest' => '1', 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'player_pseudonym' => '', 'auction_end' => 600],
			['amount' => 2000, 'hand_money' => 0, 'contract_matches' => 10, 'contract_salary' => 100, 'contract_goalbonus' => 200, 'date' => 700, 'ishighest' => '1', 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'player_pseudonym' => '', 'auction_end' => 600],
			['amount' => 500, 'hand_money' => 0, 'contract_matches' => 10, 'contract_salary' => 100, 'contract_goalbonus' => 200, 'date' => 500, 'ishighest' => '1', 'player_id' => 2, 'player_firstname' => 'C', 'player_lastname' => 'D', 'player_pseudonym' => '', 'auction_end' => 700],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$bids = TransfermarketDataService::getCurrentBidsOfTeam($this->ws, $db, 7);
		$this->assertCount(2, $bids);
		$this->assertArrayHasKey(1, $bids);
		$this->assertArrayHasKey(2, $bids);
		// first bid for player 1 is kept (amount 1000)
		$this->assertSame(1000, $bids[1]['amount']);
	}

	public function testGetCurrentBidsOfTeamReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TransfermarketDataService::getCurrentBidsOfTeam($this->ws, $db, 7));
	}

	public function testGetLatestBidOfUserReturnsBid(): void {
		$row = ['amount' => 1000, 'hand_money' => 0, 'contract_matches' => 10, 'contract_salary' => 100, 'contract_goalbonus' => 200, 'date' => 500, 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'auction_end' => 600];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, TransfermarketDataService::getLatestBidOfUser($this->ws, $db, 5));
	}

	public function testGetLatestBidOfUserReturnsFalseWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(TransfermarketDataService::getLatestBidOfUser($this->ws, $db, 5));
	}

	public function testGetCompletedTransfersOfUserReturnsListWithAmountFields(): void {
		$rows = [
			['transfer_date' => 100, 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'from_id' => 1, 'from_name' => 'X', 'to_id' => 2, 'to_name' => 'Y', 'directtransfer_amount' => 500, 'exchangeplayer1_id' => null, 'exchangeplayer1_pseudonym' => null, 'exchangeplayer1_firstname' => null, 'exchangeplayer1_lastname' => null, 'exchangeplayer2_id' => null, 'exchangeplayer2_pseudonym' => null, 'exchangeplayer2_firstname' => null, 'exchangeplayer2_lastname' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$transfers = TransfermarketDataService::getCompletedTransfersOfUser($this->ws, $db, 5);
		$this->assertCount(1, $transfers);
		$this->assertSame(0, $transfers[0]['hand_money']);
		$this->assertSame(500, $transfers[0]['amount']);
	}

	public function testGetCompletedTransfersOfUserReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TransfermarketDataService::getCompletedTransfersOfUser($this->ws, $db, 5));
	}

	public function testGetCompletedTransfersOfTeamReturnsList(): void {
		$rows = [
			['transfer_date' => 100, 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'from_id' => 1, 'from_name' => 'X', 'to_id' => 2, 'to_name' => 'Y', 'directtransfer_amount' => 500, 'exchangeplayer1_id' => null, 'exchangeplayer1_pseudonym' => null, 'exchangeplayer1_firstname' => null, 'exchangeplayer1_lastname' => null, 'exchangeplayer2_id' => null, 'exchangeplayer2_pseudonym' => null, 'exchangeplayer2_firstname' => null, 'exchangeplayer2_lastname' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertCount(1, TransfermarketDataService::getCompletedTransfersOfTeam($this->ws, $db, 7));
	}

	public function testGetCompletedTransfersOfPlayerReturnsList(): void {
		$rows = [
			['transfer_date' => 100, 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'from_id' => 1, 'from_name' => 'X', 'to_id' => 2, 'to_name' => 'Y', 'directtransfer_amount' => 500, 'exchangeplayer1_id' => null, 'exchangeplayer1_pseudonym' => null, 'exchangeplayer1_firstname' => null, 'exchangeplayer1_lastname' => null, 'exchangeplayer2_id' => null, 'exchangeplayer2_pseudonym' => null, 'exchangeplayer2_firstname' => null, 'exchangeplayer2_lastname' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertCount(1, TransfermarketDataService::getCompletedTransfersOfPlayer($this->ws, $db, 1));
	}

	public function testGetLastCompletedTransfersReturnsList(): void {
		$rows = [
			['transfer_date' => 100, 'player_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B', 'from_id' => 1, 'from_name' => 'X', 'to_id' => 2, 'to_name' => 'Y', 'directtransfer_amount' => 500, 'exchangeplayer1_id' => null, 'exchangeplayer1_pseudonym' => null, 'exchangeplayer1_firstname' => null, 'exchangeplayer1_lastname' => null, 'exchangeplayer2_id' => null, 'exchangeplayer2_pseudonym' => null, 'exchangeplayer2_firstname' => null, 'exchangeplayer2_lastname' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertCount(1, TransfermarketDataService::getLastCompletedTransfers($this->ws, $db));
	}

	public function testGetTransactionsBetweenUsersReturnsNumber(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['number' => 3]]));
		$this->assertSame(3, TransfermarketDataService::getTransactionsBetweenUsers($this->ws, $db, 1, 2));
	}

	public function testGetTransactionsBetweenUsersReturnsZeroWhenNoHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['foo' => 'bar']]));
		$this->assertSame(0, TransfermarketDataService::getTransactionsBetweenUsers($this->ws, $db, 1, 2));
	}

	public function testAwardUserForTradesReturnsEarlyWhenNoTransactions(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 0]]));
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryUpdate');
		TransfermarketDataService::awardUserForTrades($this->ws, $db, 5);
		$this->assertTrue(true);
	}

	public function testAwardUserForTradesCallsBadgeServiceWhenTransactionsExist(): void {
		// First querySelect returns hits; the badge lookup (inside
		// BadgesDataService::awardBadgeIfApplicable) returns no badge -> early return.
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['hits' => 5]]),
			$this->dbResult([]) // badge lookup -> no badge -> returns
		);
		TransfermarketDataService::awardUserForTrades($this->ws, $db, 5);
		$this->assertTrue(true);
	}

	public function testMovePlayersWithoutTeamToTransfermarketDoesNothingWhenNoPlayers(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryUpdate');
		TransfermarketDataService::movePlayersWithoutTeamToTransfermarket($this->ws, $db);
	}

	public function testMovePlayersWithoutTeamToTransfermarketMovesPlayerFromManagedTeam(): void {
		// Player from a managed team (contract ended) -> moved to transfer market
		// and the manager's contract-extension inactivity field is increased.
		$players = [['id' => 1, 'verein_id' => 5]];
		$inactivityRow = ['id' => 9, 'user_id' => 10, 'contractextensions' => 0, 'login_check' => 0, 'transfer_check' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult($players),        // players to move
			$this->dbResult([$inactivityRow]) // getUserInactivity inside increaseContractExtensionField
		);
		$db->method('queryCachedSelect')->willReturn([['team_id' => 5, 'user_id' => 10]]);
		$updates = [];
		$db->method('queryUpdate')->willReturnCallback(function ($cols) use (&$updates) {
			$updates[] = $cols;
		});
		TransfermarketDataService::movePlayersWithoutTeamToTransfermarket($this->ws, $db);
		// first update: inactivity field; second update: player
		$this->assertCount(2, $updates);
		$this->assertArrayHasKey('vertragsauslauf', $updates[0]);
		$playerUpdate = $updates[1];
		$this->assertSame('1', $playerUpdate['transfermarkt']);
		$this->assertSame('', $playerUpdate['verein_id']);
		$this->assertSame(0, $playerUpdate['transfer_mindestgebot']);
		$this->assertSame($playerUpdate['transfer_start'] + 24 * 3600 * 3, $playerUpdate['transfer_ende']);
	}

	public function testMovePlayersWithoutTeamToTransfermarketKeepsPlayerInManagerlessTeam(): void {
		$players = [['id' => 2, 'verein_id' => 5]];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($players));
		$db->method('queryCachedSelect')->willReturn([['team_id' => 5, 'user_id' => 0]]);
		$captured = null;
		$db->method('queryUpdate')->willReturnCallback(function ($cols) use (&$captured) {
			$captured = $cols;
		});
		TransfermarketDataService::movePlayersWithoutTeamToTransfermarket($this->ws, $db);
		$this->assertNotNull($captured);
		$this->assertSame('0', $captured['transfermarkt']);
		$this->assertSame('5', $captured['vertrag_spiele']);
		$this->assertSame(5, $captured['verein_id']);
	}

	public function testExecuteOpenTransfersDoesNothingWhenNoEndedAuctions(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryUpdate');
		$db->expects($this->never())->method('queryInsert');
		TransfermarketDataService::executeOpenTransfers($this->ws, $db);
	}

	public function testExecuteOpenTransfersExtendsAuctionWhenNoBid(): void {
		// Ended auction with no bid -> extendDuration() updates transfer_ende.
		$player = ['player_id' => 1, 'transfer_start' => 100, 'transfer_end' => 200,
			'first_name' => 'A', 'last_name' => 'B', 'pseudonym' => '',
			'team_id' => 0, 'team_name' => '', 'team_user_id' => 0];
		$db = $this->createMock(\DbConnection::class);
		// 1st call: ended-auctions query; 2nd call: getHighestBidForPlayer (no bid).
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([$player]),
			$this->dbResult([])
		);
		$updates = [];
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updates) {
			$updates[] = ['columns' => $columns, 'fromTable' => $fromTable, 'parameters' => $parameters];
		});
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryDelete');

		TransfermarketDataService::executeOpenTransfers($this->ws, $db);

		$this->assertCount(1, $updates);
		$this->assertSame('ws_spieler', $updates[0]['fromTable']);
		$this->assertSame(1, $updates[0]['parameters']);
		// transfer_ende = now + 3 days (transfermarket_duration_days = 3)
		$expectedEnd = $this->ws->getNowAsTimestamp() + 24 * 3600 * 3;
		$this->assertSame($expectedEnd, $updates[0]['columns']['transfer_ende']);
	}

	public function testExecuteOpenTransfersTransfersPlayerWhenBidExists(): void {
		// Ended auction with a winning bid -> transferPlayer() moves the player,
		// logs the transfer, notifies the buyer and deletes old bids.
		$player = ['player_id' => 1, 'transfer_start' => 100, 'transfer_end' => 200,
			'first_name' => 'A', 'last_name' => 'B', 'pseudonym' => 'Ace',
			'team_id' => 5, 'team_name' => 'FC', 'team_user_id' => 9];
		$bid = ['bid_id' => 7, 'amount' => 1000, 'hand_money' => 0,
			'contract_matches' => 10, 'contract_salary' => 100, 'contract_goalbonus' => 20,
			'date' => 150, 'team_id' => 6, 'team_name' => 'Rivals', 'user_id' => 8, 'user_name' => 'u'];

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'transfermarket_duration_days' => 3,
			'no_transactions_for_teams_without_user' => '0',
		]);

		$db = $this->createMock(\DbConnection::class);
		// Dispatch querySelect by table: ended-auctions query, highest-bid lookup,
		// and the COUNT(*) queries issued by awardUserForTrades (return 0 hits so
		// the badge service is never invoked).
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($player, $bid) {
			if ($fromTable === 'ws_spieler AS P' . "\n" || str_contains($fromTable, 'LEFT JOIN')) {
				return $this->dbResult([$player]);
			}
			if (str_contains($fromTable, '_transfer_angebot AS B')) {
				return $this->dbResult([$bid]);
			}
			if ($columns === 'COUNT(*) AS hits') {
				return $this->dbResult([['hits' => 0]]);
			}
			return $this->dbResult([]);
		});
		// Team summaries for the debited buyer (6) and credited seller (5).
		$db->method('queryCachedSelect')->willReturnOnConsecutiveCalls(
			[['team_id' => 6, 'user_id' => 8, 'team_budget' => 5000]],
			[['team_id' => 5, 'user_id' => 9, 'team_budget' => 3000]]
		);

		$updates = [];
		$inserts = [];
		$deletes = [];
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable) use (&$updates) {
			$updates[] = ['columns' => $columns, 'fromTable' => $fromTable];
		});
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserts) {
			$inserts[] = ['columns' => $columns, 'fromTable' => $fromTable];
		});
		$db->method('queryDelete')->willReturnCallback(function ($fromTable) use (&$deletes) {
			$deletes[] = $fromTable;
		});

		TransfermarketDataService::executeOpenTransfers($ws, $db);

		// The player must be moved to the buyer's team with the bid's contract terms.
		$playerUpdate = null;
		foreach ($updates as $u) {
			if ($u['fromTable'] === 'ws_spieler') {
				$playerUpdate = $u['columns'];
			}
		}
		$this->assertNotNull($playerUpdate);
		$this->assertSame(6, $playerUpdate['verein_id']);
		$this->assertSame(10, $playerUpdate['vertrag_spiele']);
		$this->assertSame(0, $playerUpdate['transfermarkt']);

		// A transfer log entry must be created for the buyer.
		$logInserts = array_values(array_filter($inserts, fn($i) => $i['fromTable'] === 'ws_transfer'));
		$this->assertCount(1, $logInserts);
		$this->assertSame(1, $logInserts[0]['columns']['spieler_id']);
		$this->assertSame(8, $logInserts[0]['columns']['buyer_user_id']);

		// Old bids must be deleted.
		$this->assertContains('ws_transfer_angebot', $deletes);
	}
}
