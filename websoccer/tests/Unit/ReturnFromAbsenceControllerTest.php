<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for ReturnFromAbsenceController.
 */
final class ReturnFromAbsenceControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testReturnsOfficeWhenUserHasNoAbsence(): void {
		$i18n = $this->mockI18n(['absence_return_success' => 'Welcome back']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		// No absence record -> confirmComeback() returns early.
		$db = $this->mockDb();

		$controller = new ReturnFromAbsenceController($i18n, $ws, $db);
		$this->assertSame('office', $controller->executeAction([]));
	}

	public function testReturnsOfficeAndGivesBackTeamsWhenAbsent(): void {
		$i18n = $this->mockI18n(['absence_return_success' => 'Welcome back']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'gravatar_enable' => FALSE]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));

		$db = $this->makeDb([], [
			// getCurrentAbsenceOfUser -> absence with deputy
			'ws_userabsence' => [['user_id' => 1, 'deputy_id' => 2]],
			// getUserById (for deputy notification)
			'ws_user' => [['id' => 1, 'nick' => 'manager', 'email' => 'm@e.com', 'picture' => '']],
		]);

		$updates = 0;
		$db->method('queryUpdate')->willReturnCallback(function () use (&$updates) { $updates++; });
		$db->method('queryDelete');

		$controller = new ReturnFromAbsenceController($i18n, $ws, $db);
		$this->assertSame('office', $controller->executeAction([]));
		$this->assertGreaterThanOrEqual(1, $updates);
	}
}
