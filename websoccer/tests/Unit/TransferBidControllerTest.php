<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for TransferBidController.
 */
final class TransferBidControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'transfermarket_computed_marketvalue' => FALSE,
			'transfermarket_enabled' => TRUE,
		];
	}

	public function testReturnsNullWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['transfermarket_enabled' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryInsert');

		$controller = new TransferBidController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
	}

	public function testThrowsWhenUserHasNoClub(): void {
		$i18n = $this->mockI18n(['error_action_required_team' => 'requires team']);
		$ws = $this->mockWebsoccer($this->config());
		// Guest -> getClubId() returns null (< 1).
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('requires team');

		$controller = new TransferBidController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}

	public function testThrowsWhenBiddingOnOwnPlayer(): void {
		$i18n = $this->mockI18n(['transfer_bid_on_own_player' => 'own player']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// Player is managed by the same user (team_user_id == 1).
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 2, 'team_user_id' => 1])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('own player');

		$controller = new TransferBidController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}

	public function testThrowsWhenPlayerNotOnTransferList(): void {
		$i18n = $this->mockI18n(['transfer_bid_player_not_on_list' => 'not on list']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow([
			'team_id' => 2, 'team_user_id' => 2, 'player_transfermarket' => 0,
		])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not on list');

		$controller = new TransferBidController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}

	public function testThrowsWhenAuctionEnded(): void {
		$i18n = $this->mockI18n(['transfer_bid_auction_ended' => 'ended']);
		$ws = $this->mockWebsoccerAt(2000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow([
			'team_id' => 2, 'team_user_id' => 2, 'player_transfermarket' => 1,
			'transfer_end' => 1000000,
		])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('ended');

		$controller = new TransferBidController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}
}
