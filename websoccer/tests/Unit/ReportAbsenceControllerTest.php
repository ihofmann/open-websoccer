<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for ReportAbsenceController.
 */
final class ReportAbsenceControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenDeputyNotFound(): void {
		$i18n = $this->mockI18n(['absence_err_invaliddeputy' => 'invalid deputy']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		// getUserIdByNick returns no row -> -1.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('invalid deputy');

		$controller = new ReportAbsenceController($i18n, $ws, $db);
		$controller->executeAction(['deputynick' => 'ghost', 'days' => 3]);
	}

	public function testThrowsWhenDeputyIsSelf(): void {
		$i18n = $this->mockI18n(['absence_err_deputyisself' => 'self']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->makeDb([], ['ws_user' => [['id' => 1]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('self');

		$controller = new ReportAbsenceController($i18n, $ws, $db);
		$controller->executeAction(['deputynick' => 'manager', 'days' => 3]);
	}

	public function testReportsAbsenceAndReturnsNull(): void {
		$i18n = $this->mockI18n(['absence_report_success' => 'reported']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'gravatar_enable' => FALSE]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		// getUserIdByNick -> deputy 5; getUserById (called inside makeUserAbsent)
		// reuses the same table; provide a fully-populated row to avoid warnings.
		$db = $this->makeDb([], [
			'ws_user' => [['id' => 5, 'nick' => 'deputy', 'email' => 'd@e.com', 'picture' => '']],
		]);

		$inserted = false;
		$db->method('queryInsert')->willReturnCallback(function () use (&$inserted) { $inserted = true; });
		$db->method('queryUpdate');

		$controller = new ReportAbsenceController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['deputynick' => 'deputy', 'days' => 3]));
		$this->assertTrue($inserted);
	}
}
