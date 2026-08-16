<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SelectCaptainController.
 */
final class SelectCaptainControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'transfermarket_computed_marketvalue' => FALSE,
		];
	}

	public function testSelectsCaptainAndReturnsNull(): void {
		$i18n = $this->mockI18n(['myteam_player_select_as_captain_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb([
			// getTeamById -> no previous captain.
			'_verein AS C' => [$this->teamRow(['captain_id' => 0])],
			// getPlayerById -> own player.
			'_spieler AS P' => [$this->playerRow(['team_id' => 1, 'player_id' => 7])],
		]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new SelectCaptainController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 7]));
		$this->assertSame(7, $updated[0]['captain_id']);
		$this->assertSame(1, $updated[3]);
	}

	public function testThrowsWhenPlayerNotOwned(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb([
			'_verein AS C' => [$this->teamRow(['captain_id' => 0])],
			'_spieler AS P' => [$this->playerRow(['team_id' => 2, 'player_id' => 7])],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('nice try');

		$controller = new SelectCaptainController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}
}
