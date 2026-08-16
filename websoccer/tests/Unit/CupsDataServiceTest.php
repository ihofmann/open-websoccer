<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for CupsDataService.
 */
final class CupsDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(['db_prefix' => 'ws']);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetTeamsOfCupGroupInRankingOrderReturnsTeams(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'name' => 'Team A', 'user_id' => '7', 'user_name' => 'joe', 'score' => '9', 'goals' => '10', 'goals_received' => '4', 'wins' => '3', 'draws' => '0', 'defeats' => '1'],
			['id' => '2', 'name' => 'Team B', 'user_id' => '', 'user_name' => null, 'score' => '6', 'goals' => '7', 'goals_received' => '6', 'wins' => '2', 'draws' => '0', 'defeats' => '2'],
		]));
		$teams = CupsDataService::getTeamsOfCupGroupInRankingOrder($ws, $db, 10, 'Group A');
		$this->assertCount(2, $teams);
		$this->assertSame('Team A', $teams[0]['name']);
		$this->assertSame('9', $teams[0]['score']);
	}

	public function testGetTeamsOfCupGroupInRankingOrderReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], CupsDataService::getTeamsOfCupGroupInRankingOrder($ws, $db, 10, 'Group A'));
	}
}
