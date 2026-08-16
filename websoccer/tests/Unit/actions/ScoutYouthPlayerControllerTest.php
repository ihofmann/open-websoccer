<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for ScoutYouthPlayerController.
 */
final class ScoutYouthPlayerControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'youth_enabled' => TRUE,
			'youth_scouting_enabled' => TRUE,
			'youth_scouting_break_hours' => 24,
		];
	}

	public function testThrowsWhenUserHasNoClub(): void {
		$i18n = $this->mockI18n(['error_action_required_team' => 'requires team']);
		$ws = $this->mockWebsoccer($this->config());
		// Guest -> getClubId() returns null (< 1).
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('requires team');

		$controller = new ScoutYouthPlayerController($i18n, $ws, $db);
		$controller->executeAction(['country' => 'Germany', 'scoutid' => 1]);
	}

	public function testThrowsWhenScoutingBreakViolated(): void {
		$i18n = $this->mockI18n(['youthteam_scouting_err_breakviolation' => 'break violated %s']);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// Last scouting was "now" -> next possible execution is in the future.
		$db = $this->makeDb([], ['ws_verein' => [['scouting_last_execution' => 1000000]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('break violated');

		$controller = new ScoutYouthPlayerController($i18n, $ws, $db);
		$controller->executeAction(['country' => 'Germany', 'scoutid' => 1]);
	}

	public function testThrowsForInvalidCountry(): void {
		$i18n = $this->mockI18n(['youthteam_scouting_err_invalidcountry' => 'invalid country']);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// No previous scouting (0) -> break check passes; then country files missing.
		$db = $this->makeDb([], ['ws_verein' => [['scouting_last_execution' => 0]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('invalid country');

		$controller = new ScoutYouthPlayerController($i18n, $ws, $db);
		$controller->executeAction(['country' => 'Atlantis', 'scoutid' => 1]);
	}
}
