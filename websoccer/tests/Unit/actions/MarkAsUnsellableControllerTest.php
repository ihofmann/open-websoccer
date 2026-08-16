<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for MarkAsUnsellableController.
 */
final class MarkAsUnsellableControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'transfermarket_computed_marketvalue' => FALSE,
		];
	}

	public function testMarksOwnPlayerAndReturnsNull(): void {
		$i18n = $this->mockI18n(['myteam_unsellable_player_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 1])]]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new MarkAsUnsellableController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
		$this->assertSame(1, $updated[0]['unsellable']);
	}

	public function testThrowsWhenPlayerNotOwned(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 2])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('nice try');

		$controller = new MarkAsUnsellableController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}
}
