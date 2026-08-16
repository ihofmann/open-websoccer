<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for RemovePlayerFromTransfermarketController.
 */
final class RemovePlayerFromTransfermarketControllerTest extends TestCaseBase {
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
		$db->expects($this->never())->method('queryUpdate');

		$controller = new RemovePlayerFromTransfermarketController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
	}

	public function testRemovesOwnPlayerWithoutBidAndReturnsMyteam(): void {
		$i18n = $this->mockI18n(['transfermarket_remove_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// No existing bid -> getHighestBidForPlayer returns false.
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 1])]]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new RemovePlayerFromTransfermarketController($i18n, $ws, $db);
		$this->assertSame('myteam', $controller->executeAction(['id' => 7]));
		$this->assertSame('0', $updated[0]['transfermarkt']);
		$this->assertSame(7, $updated[3]);
	}

	public function testThrowsWhenBidExists(): void {
		$i18n = $this->mockI18n(['transfermarket_remove_err_bidexists' => 'bid exists']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// An existing highest bid.
		$db = $this->makeDb(
			['_spieler AS P' => [$this->playerRow(['team_id' => 1])]],
			['_transfer_angebot' => [['bid_id' => 1, 'amount' => 1000]]]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('bid exists');

		$controller = new RemovePlayerFromTransfermarketController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}

	public function testThrowsWhenNotOwnPlayer(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 2])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('nice try');

		$controller = new RemovePlayerFromTransfermarketController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}
}
