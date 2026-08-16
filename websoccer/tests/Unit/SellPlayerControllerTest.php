<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SellPlayerController.
 */
final class SellPlayerControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'transfermarket_computed_marketvalue' => FALSE,
			'transfermarket_enabled' => TRUE,
			'transfermarket_min_teamsize' => 18,
			'transfermarket_duration_days' => 3,
		];
	}

	public function testReturnsNullWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['transfermarket_enabled' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new SellPlayerController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1, 'min_bid' => 50000]));
	}

	public function testSellsOwnPlayerAndReturnsTransfermarket(): void {
		$i18n = $this->mockI18n(['sell_player_success' => 'ok']);
		$ws = $this->mockWebsoccerAt(100000, $this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			['_spieler AS P' => [$this->playerRow([
				'team_id' => 1, 'player_transfermarket' => 0, 'lending_fee' => 0,
				'player_marketvalue' => 100000, 'player_id' => 7,
			])]],
			['ws_spieler' => [['number' => 20]]]
		);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new SellPlayerController($i18n, $ws, $db);
		$this->assertSame('transfermarket', $controller->executeAction(['id' => 7, 'min_bid' => 60000]));
		$this->assertSame(1, $updated[0]['transfermarkt']);
		$this->assertSame(60000, $updated[0]['transfer_mindestgebot']);
		$this->assertSame(7, $updated[3]);
	}

	public function testThrowsWhenAlreadyOnMarket(): void {
		$i18n = $this->mockI18n(['sell_player_already_on_list' => 'on list']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 1, 'player_transfermarket' => 1])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('on list');

		$controller = new SellPlayerController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7, 'min_bid' => 60000]);
	}

	public function testThrowsWhenMinBidTooLow(): void {
		$i18n = $this->mockI18n(['sell_player_min_bid_too_low' => 'too low']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			['_spieler AS P' => [$this->playerRow([
				'team_id' => 1, 'player_transfermarket' => 0, 'lending_fee' => 0,
				'player_marketvalue' => 100000,
			])]],
			['ws_spieler' => [['number' => 20]]]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('too low');

		$controller = new SellPlayerController($i18n, $ws, $db);
		// marketvalue/2 = 50000; bid 40000 is too low.
		$controller->executeAction(['id' => 7, 'min_bid' => 40000]);
	}
}
