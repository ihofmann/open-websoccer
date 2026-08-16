<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for LeagueDataService.
 */
final class LeagueDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(['db_prefix' => 'ws']);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetLeagueByIdReturnsLeagueWhenFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'league_id' => '5', 'league_name' => 'Premier League', 'league_short' => 'PL', 'league_country' => 'England',
		]]);
		$league = LeagueDataService::getLeagueById($ws, $db, 5);
		$this->assertSame('Premier League', $league['league_name']);
		$this->assertSame('England', $league['league_country']);
	}

	public function testGetLeagueByIdReturnsEmptyArrayWhenNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], LeagueDataService::getLeagueById($ws, $db, 999));
	}

	public function testGetLeaguesSortedByCountryReturnsList(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([
			['league_id' => '1', 'league_name' => 'Bundesliga', 'league_short' => 'BL', 'league_country' => 'Deutschland'],
			['league_id' => '2', 'league_name' => 'Premier League', 'league_short' => 'PL', 'league_country' => 'England'],
		]);
		$leagues = LeagueDataService::getLeaguesSortedByCountry($ws, $db);
		$this->assertCount(2, $leagues);
		$this->assertSame('Bundesliga', $leagues[0]['league_name']);
	}

	public function testGetLeaguesSortedByCountryReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], LeagueDataService::getLeaguesSortedByCountry($ws, $db));
	}

	public function testCountTotalLeaguesReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '7']]));
		$this->assertSame('7', LeagueDataService::countTotalLeagues($ws, $db));
	}

	public function testCountTotalLeaguesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, LeagueDataService::countTotalLeagues($ws, $db));
	}
}
