<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for UnmarkLendableController.
 */
final class UnmarkLendableControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'transfermarket_computed_marketvalue' => FALSE,
		];
	}

	public function testUnmarksLendableOwnPlayerAndReturnsNull(): void {
		$i18n = $this->mockI18n(['lending_lendable_unmark_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 1, 'lending_owner_id' => 0])]]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new UnmarkLendableController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
		$this->assertSame(0, $updated[0]['lending_fee']);
	}

	public function testThrowsWhenPlayerNotOwned(): void {
		$i18n = $this->mockI18n(['lending_err_notownplayer' => 'not own']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 2])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not own');

		$controller = new UnmarkLendableController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}

	public function testThrowsWhenPlayerIsBorrowed(): void {
		$i18n = $this->mockI18n(['lending_err_borrowed_player' => 'borrowed']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 1, 'lending_owner_id' => 3])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('borrowed');

		$controller = new UnmarkLendableController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}
}
