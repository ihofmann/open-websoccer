<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SaveFormationController.
 */
final class SaveFormationControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenNoNextMatch(): void {
		$i18n = $this->mockI18n(['formation_err_nonextmatch' => 'no next match']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'formation_max_next_matches' => 5]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// getNextMatches -> empty result set.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('no next match');

		$controller = new SaveFormationController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}

	public function testThrowsForIllegalMatchId(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'formation_max_next_matches' => 5]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// A next match exists (id 5) but the requested id (99) is not among them.
		$db = $this->makeDb([], [
			'_spiel AS M' => [['match_id' => 5, 'match_type' => 'league', 'match_home_id' => 1, 'match_guest_id' => 2]],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal match id');

		$controller = new SaveFormationController($i18n, $ws, $db);
		$controller->executeAction(['id' => 99]);
	}
}
