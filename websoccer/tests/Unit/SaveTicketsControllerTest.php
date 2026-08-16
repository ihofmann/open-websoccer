<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SaveTicketsController.
 */
final class SaveTicketsControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testReturnsNullWhenUserHasNoClub(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		// Guest user: id null -> getClubId() returns null.
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new SaveTicketsController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction([
			'p_stands' => 10, 'p_seats' => 20, 'p_stands_grand' => 30,
			'p_seats_grand' => 40, 'p_vip' => 50,
		]));
	}

	public function testUpdatesTicketPricesForClubAndReturnsNull(): void {
		$i18n = $this->mockI18n(['saved_message_title' => 'Saved']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 7));
		$db = $this->createMock(\DbConnection::class);
		$captured = null;
		$db->expects($this->once())->method('queryUpdate')
			->willReturnCallback(function ($columns, $table, $where, $params) use (&$captured) {
				$captured = [$columns, $table, $where, $params];
			});

		$controller = new SaveTicketsController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction([
			'p_stands' => 11, 'p_seats' => 22, 'p_stands_grand' => 33,
			'p_seats_grand' => 44, 'p_vip' => 55,
		]));

		$this->assertSame('ws_verein', $captured[1]);
		$this->assertSame('id = %d', $captured[2]);
		$this->assertSame(7, $captured[3]);
		$this->assertSame(11, $captured[0]['preis_stehen']);
		$this->assertSame(55, $captured[0]['preis_vip']);
	}
}
