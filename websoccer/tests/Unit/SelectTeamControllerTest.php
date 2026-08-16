<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SelectTeamController.
 */
final class SelectTeamControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testReturnsNullWhenTeamAlreadySelected(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 5));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		$controller = new SelectTeamController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testSelectsTeamAndReturnsNull(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 5));
		$db = $this->makeDb([], [
			'ws_verein' => [['id' => 9]],
		]);

		$controller = new SelectTeamController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 9]));
		// setClubId() stores into the session.
		$this->assertSame(9, $_SESSION['clubid']);
	}

	public function testThrowsForIllegalClubId(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 5));
		// No matching club row.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal club ID');

		$controller = new SelectTeamController($i18n, $ws, $db);
		$controller->executeAction(['id' => 999]);
	}
}
