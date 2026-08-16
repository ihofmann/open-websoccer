<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for AbsencesDataService.
 */
final class AbsencesDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		$ws = $this->mockWebsoccer(array_merge([
			'db_prefix' => 'ws',
			'context_root' => '/ws',
			'gravatar_enable' => '0',
		], $config));
		return $ws;
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetCurrentAbsenceOfUserReturnsRowWhenPresent(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['user_id' => '7', 'deputy_id' => '9', 'from_date' => '100', 'to_date' => '200'],
		]));
		$absence = AbsencesDataService::getCurrentAbsenceOfUser($ws, $db, 7);
		$this->assertSame('9', $absence['deputy_id']);
	}

	public function testGetCurrentAbsenceOfUserReturnsFalseWhenAbsent(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertFalse(AbsencesDataService::getCurrentAbsenceOfUser($ws, $db, 7));
	}

	public function testMakeUserAbsentInsertsRecordUpdatesTeamsAndNotifies(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// getUserById runs one querySelect; return a user with nick.
		$db->method('querySelect')->willReturn($this->dbResult([
			['nick' => 'joe', 'picture' => 'pic.jpg', 'email' => 'e@e.com'],
		]));
		$db->expects($this->exactly(2))->method('queryInsert');
		$db->expects($this->once())->method('queryUpdate');

		AbsencesDataService::makeUserAbsent($ws, $db, 7, 9, 5);
	}

	public function testConfirmComebackDoesNothingWhenNoAbsence(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryUpdate');
		$db->expects($this->never())->method('queryDelete');
		$db->expects($this->never())->method('queryInsert');

		AbsencesDataService::confirmComeback($ws, $db, 7);
	}

	public function testConfirmComebackGivesBackTeamsAndDeletesAbsenceWithoutDeputy(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// getCurrentAbsenceOfUser query (no deputy -> no notification queries).
		$db->method('querySelect')->willReturn($this->dbResult([
			['user_id' => '7', 'deputy_id' => '', 'from_date' => '100', 'to_date' => '200'],
		]));
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->once())->method('queryDelete');
		$db->expects($this->never())->method('queryInsert');

		AbsencesDataService::confirmComeback($ws, $db, 7);
	}

	public function testConfirmComebackNotifiesDeputyWhenSet(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// 1st query: absence; 2nd query: getUserById.
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['user_id' => '7', 'deputy_id' => '9', 'from_date' => '100', 'to_date' => '200']]),
			$this->dbResult([['nick' => 'joe', 'picture' => 'pic.jpg', 'email' => 'e@e.com']])
		);
		$db->expects($this->once())->method('queryUpdate');
		$db->expects($this->once())->method('queryDelete');
		// one insert for the deputy notification.
		$db->expects($this->once())->method('queryInsert');

		AbsencesDataService::confirmComeback($ws, $db, 7);
	}
}
