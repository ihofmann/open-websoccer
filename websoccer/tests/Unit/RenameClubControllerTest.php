<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for RenameClubController.
 */
final class RenameClubControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testRenamesClubAndStadiumAndReturnsLeague(): void {
		$i18n = $this->mockI18n(['rename-club_success' => 'ok']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'rename_club_enabled' => TRUE]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_verein AS C' => [$this->teamRow(['team_id' => 1])]]);

		$updates = [];
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updates) { $updates[] = [$c, $t, $w, $p]; });

		$controller = new RenameClubController($i18n, $ws, $db);
		$this->assertSame('league', $controller->executeAction(['name' => 'FC New', 'kurz' => 'fcn', 'stadium' => 'New Arena']));
		// First update is the club rename.
		$this->assertSame('FC New', $updates[0][0]['name']);
		$this->assertSame('FCN', $updates[0][0]['kurz']);
		// Second update targets the stadium table join.
		$this->assertStringContainsString('_stadion', $updates[1][1]);
		$this->assertSame('New Arena', $updates[1][0]['S.name']);
	}

	public function testReturnsNullWhenTeamNotFound(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'rename_club_enabled' => TRUE]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// getTeamSummaryById returns empty array -> !$team.
		$db = $this->makeDb(['_verein AS C' => []]);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new RenameClubController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['name' => 'FC', 'kurz' => 'fc', 'stadium' => 'Arena']));
	}

	public function testThrowsErrorWhenFeatureDisabled(): void {
		// The controller contains a typo: "throw new Exceltion(...)". PHP raises
		// a fatal \Error because the class does not exist.
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'rename_club_enabled' => FALSE]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->mockDb();

		$this->expectException(\Error::class);

		$controller = new RenameClubController($i18n, $ws, $db);
		$controller->executeAction(['name' => 'FC', 'kurz' => 'fc', 'stadium' => 'Arena']);
	}
}
