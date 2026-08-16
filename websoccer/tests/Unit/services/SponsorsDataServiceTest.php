<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SponsorsDataService.
 */
final class SponsorsDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
	}

	public function testGetSponsorinfoByTeamIdReturnsRow(): void {
		$row = [
			'matchdays' => 5,
			'sponsor_id' => 10,
			'name' => 'Acme',
			'amount_match' => 1000,
			'amount_home_bonus' => 200,
			'amount_win' => 500,
			'amount_championship' => 5000,
			'picture' => 'acme.jpg',
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, SponsorsDataService::getSponsorinfoByTeamId($this->ws, $db, 7));
	}

	public function testGetSponsorinfoByTeamIdReturnsFalseWhenNoSponsor(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(SponsorsDataService::getSponsorinfoByTeamId($this->ws, $db, 7));
	}

	public function testGetSponsorOffersReturnsCachedRows(): void {
		// querySelect is used by getTableRankOfTeam; queryCachedSelect is used
		// by getTeamSummaryById and finally by getSponsorOffers itself.
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['RNK' => '3']]));
		$db->method('queryCachedSelect')->willReturnOnConsecutiveCalls(
			[['team_id' => 7, 'team_league_id' => 2]],
			[
				['sponsor_id' => 1, 'name' => 'A', 'amount_match' => 100, 'amount_home_bonus' => 10, 'amount_win' => 50, 'amount_championship' => 1000],
				['sponsor_id' => 2, 'name' => 'B', 'amount_match' => 80, 'amount_home_bonus' => 5, 'amount_win' => 40, 'amount_championship' => 800],
			]
		);
		$offers = SponsorsDataService::getSponsorOffers($this->ws, $db, 7);
		$this->assertCount(2, $offers);
		$this->assertSame('A', $offers[0]['name']);
		$this->assertSame('B', $offers[1]['name']);
	}

	public function testGetSponsorOffersReturnsEmptyWhenNoOffers(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['RNK' => '1']]));
		$db->method('queryCachedSelect')->willReturnOnConsecutiveCalls(
			[['team_id' => 7, 'team_league_id' => 2]],
			[]
		);
		$this->assertSame([], SponsorsDataService::getSponsorOffers($this->ws, $db, 7));
	}
}
