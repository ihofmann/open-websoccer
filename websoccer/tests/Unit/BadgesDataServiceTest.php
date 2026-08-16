<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for BadgesDataService.
 */
final class BadgesDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(['db_prefix' => 'ws', 'context_root' => '/ws', 'gravatar_enable' => '0']);
	}

	public function testAwardBadgeIfApplicableDoesNothingWhenNoBadgeAvailable(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// 1st querySelect: no badge found.
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryInsert');

		BadgesDataService::awardBadgeIfApplicable($ws, $db, 7, 'x_trades', 5);
	}

	public function testAwardBadgeIfApplicableDoesNothingWhenUserHasHigherLevel(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// 1st query: badge found; 2nd query: user already has equal/higher level.
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => '1', 'name' => 'Bronze', 'level' => '1']]),
			$this->dbResult([['hits' => '1']])
		);
		$db->expects($this->never())->method('queryInsert');

		BadgesDataService::awardBadgeIfApplicable($ws, $db, 7, 'x_trades', 5);
	}

	public function testAwardBadgeIfApplicableAwardsBadgeWhenUserLacksLevel(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// 1st query: badge found; 2nd query: user has no equal/higher badge.
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => '2', 'name' => 'Silver', 'level' => '2']]),
			$this->dbResult([['hits' => '0']])
		);
		// awardBadge: one insert for assignment + one insert for notification.
		$db->expects($this->exactly(2))->method('queryInsert');

		BadgesDataService::awardBadgeIfApplicable($ws, $db, 7, 'x_trades', 10);
	}

	public function testAwardBadgeInsertsAssignmentAndNotification(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->exactly(2))->method('queryInsert');
		$db->expects($this->never())->method('querySelect');

		BadgesDataService::awardBadge($ws, $db, 7, 3);
	}

	public function testAwardBadgeIfApplicableWithNullBenchmark(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => '5', 'name' => 'Gold', 'level' => '3']]),
			$this->dbResult([['hits' => '0']])
		);
		$db->expects($this->exactly(2))->method('queryInsert');

		BadgesDataService::awardBadgeIfApplicable($ws, $db, 7, 'logins');
	}
}
