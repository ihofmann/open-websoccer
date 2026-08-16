<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SaveMatchChangesController.
 */
final class SaveMatchChangesControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenMatchNotFound(): void {
		$i18n = $this->mockI18n(['formation_err_nonextmatch' => 'no match']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// getMatchSubstitutionsById -> empty.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('no match');

		$controller = new SaveMatchChangesController($i18n, $ws, $db);
		$controller->executeAction(['id' => 99]);
	}

	public function testThrowsWhenUserIsNotAManager(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			[
				// National team lookup -> none.
				'_verein' => [],
				// getMatchSubstitutionsById uses queryCachedSelect.
				'ws_spiel AS M' => [[
					'match_id' => 5, 'match_home_id' => 10, 'match_guest_id' => 11,
					'match_simulated' => 0, 'match_minutes' => 0,
				]],
			]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('nice try');

		$controller = new SaveMatchChangesController($i18n, $ws, $db);
		$controller->executeAction(['id' => 5]);
	}

	public function testThrowsWhenMatchAlreadyCompleted(): void {
		$i18n = $this->mockI18n(['match_details_match_completed' => 'completed']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			[
				'_verein' => [],
				'ws_spiel AS M' => [[
					'match_id' => 5, 'match_home_id' => 1, 'match_guest_id' => 2,
					'match_simulated' => 1, 'match_minutes' => 45,
				]],
			]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('completed');

		$controller = new SaveMatchChangesController($i18n, $ws, $db);
		$controller->executeAction(['id' => 5]);
	}
}
