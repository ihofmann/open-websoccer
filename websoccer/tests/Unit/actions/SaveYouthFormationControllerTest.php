<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SaveYouthFormationController.
 */
final class SaveYouthFormationControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenMatchDoesNotBelongToUser(): void {
		$i18n = $this->mockI18n(['error_page_not_found' => 'not found']);
		$ws = $this->mockWebsoccerAt(1000000, ['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// Match belongs to teams 10 and 11, not the user's team 1.
		$db = $this->makeDb([], [
			'ws_youthmatch AS M' => [[
				'id' => 5, 'home_team_id' => 10, 'guest_team_id' => 11,
				'matchdate' => 2000000, 'simulated' => 0,
			]],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not found');

		$controller = new SaveYouthFormationController($i18n, $ws, $db);
		$controller->executeAction(['matchid' => 5]);
	}

	public function testThrowsWhenMatchExpired(): void {
		$i18n = $this->mockI18n(['youthformation_err_matchexpired' => 'expired']);
		$ws = $this->mockWebsoccerAt(1000000, ['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// User is home team; matchdate in the past.
		$db = $this->makeDb([], [
			'ws_youthmatch AS M' => [[
				'id' => 5, 'home_team_id' => 1, 'guest_team_id' => 2,
				'matchdate' => 0, 'simulated' => 0,
			]],
		]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('expired');

		$controller = new SaveYouthFormationController($i18n, $ws, $db);
		$controller->executeAction(['matchid' => 5]);
	}
}
