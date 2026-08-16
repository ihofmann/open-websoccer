<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CancelCampController.
 */
final class CancelCampControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($selectRowsByTable) {
				foreach ($selectRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		return $db;
	}

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId($clubId);
		return $user;
	}

	public function testExecuteActionThrowsWhenUserHasNoClub(): void {
		$user = $this->makeUser([]); // guest
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new CancelCampController(
			$this->mockI18n(['feature_requires_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['bookingid' => 5]);
	}

	public function testExecuteActionThrowsWhenNoBookingsExist(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelCampController(
			$this->mockI18n(['trainingcamp_cancel_illegalid' => 'illegal id']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('illegal id');
		$controller->executeAction(['bookingid' => 5]);
	}

	public function testExecuteActionThrowsWhenBookingIdDoesNotMatch(): void {
		$db = $this->makeDb(['_trainingslager_belegung' => [['id' => 9, 'date_start' => 1, 'date_end' => 2]]]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelCampController(
			$this->mockI18n(['trainingcamp_cancel_illegalid' => 'illegal id']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('illegal id');
		$controller->executeAction(['bookingid' => 5]);
	}

	public function testExecuteActionCancelsMatchingBookingAndReturnsPageId(): void {
		$deleted = false;
		$db = $this->makeDb(['_trainingslager_belegung' => [['id' => 5, 'date_start' => 1, 'date_end' => 2]]]);
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelCampController(
			$this->mockI18n(['trainingcamp_cancel_success' => 'cancelled']), $ws, $db);

		$this->assertSame('trainingcamp', $controller->executeAction(['bookingid' => 5]));
		$this->assertTrue($deleted);
	}
}
