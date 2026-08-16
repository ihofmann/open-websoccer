<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NationalteamsDataService.
 */
final class NationalteamsDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		$ws = $this->mockWebsoccer(array_merge([
			'db_prefix' => 'ws',
			'gravatar_enable' => '0',
			'context_root' => '/ws',
			'players_aging' => 'age',
			'transfermarket_computed_marketvalue' => '0',
		], $config));
		return $ws;
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetNationalTeamManagedByCurrentUserReturnsIdWhenFound(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 7]));
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([['id' => '15']]);
		$this->assertSame('15', NationalteamsDataService::getNationalTeamManagedByCurrentUser($ws, $db));
	}

	public function testGetNationalTeamManagedByCurrentUserReturnsNullWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 7]));
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertNull(NationalteamsDataService::getNationalTeamManagedByCurrentUser($ws, $db));
	}

	public function testGetNationalPlayersOfTeamByPositionGroupsByConvertedPosition(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'matches_injured' => '0',
			 'matches_blocked' => '0', 'position' => 'Torwart', 'position_main' => 'T', 'position_second' => '',
			 'strength' => '80', 'strength_technique' => '70', 'strength_stamina' => '60', 'strength_freshness' => '50',
			 'strength_satisfaction' => '40', 'transfermarket' => '0', 'player_nationality' => 'Deutschland', 'picture' => '',
			 'st_goals' => '0', 'st_matches' => '10', 'st_cards_yellow' => '0', 'st_cards_yellow_red' => '0', 'st_cards_red' => '0',
			 'marketvalue' => '5000', 'team_id' => '15', 'team_name' => 'Germany', 'age' => '25'],
			['id' => '2', 'firstname' => 'Amy', 'lastname' => 'Y', 'pseudonym' => '', 'matches_injured' => '0',
			 'matches_blocked' => '0', 'position' => 'Abwehr', 'position_main' => 'IV', 'position_second' => '',
			 'strength' => '75', 'strength_technique' => '65', 'strength_stamina' => '60', 'strength_freshness' => '50',
			 'strength_satisfaction' => '40', 'transfermarket' => '0', 'player_nationality' => 'England', 'picture' => '',
			 'st_goals' => '1', 'st_matches' => '8', 'st_cards_yellow' => '1', 'st_cards_yellow_red' => '0', 'st_cards_red' => '0',
			 'marketvalue' => '4000', 'team_id' => '15', 'team_name' => 'Germany', 'age' => '22'],
		]));
		$players = NationalteamsDataService::getNationalPlayersOfTeamByPosition($ws, $db, 15);
		$this->assertCount(1, $players['goaly']);
		$this->assertCount(1, $players['defense']);
		$this->assertSame('5000', $players['goaly'][0]['marketvalue']);
		$this->assertSame('Deutschland', $players['goaly'][0]['player_nationality_filename']);
	}

	public function testGetNationalPlayersOfTeamByPositionReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], NationalteamsDataService::getNationalPlayersOfTeamByPosition($ws, $db, 15));
	}

	public function testFindPlayersCountReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '12']]));
		$this->assertSame('12', NationalteamsDataService::findPlayersCount($ws, $db, 'Deutschland', 15, null, null, null, null));
	}

	public function testFindPlayersCountReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, NationalteamsDataService::findPlayersCount($ws, $db, 'Deutschland', 15, null, null, null, null));
	}

	public function testFindPlayersReturnsPlayersWithConvertedPosition(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'position' => 'Torwart',
			 'position_main' => 'T', 'position_second' => '', 'strength' => '80', 'strength_technique' => '70',
			 'strength_stamina' => '60', 'strength_freshness' => '50', 'strength_satisfaction' => '40',
			 'team_id' => '5', 'team_name' => 'Club'],
		]));
		$players = NationalteamsDataService::findPlayers($ws, $db, 'Deutschland', 15, null, null, null, null, 0, 10);
		$this->assertCount(1, $players);
		$this->assertSame('goaly', $players[0]['position']);
	}

	public function testFindPlayersReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], NationalteamsDataService::findPlayers($ws, $db, 'Deutschland', 15, null, null, null, null, 0, 10));
	}

	public function testCountNextMatchesReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '3']]));
		$this->assertSame('3', NationalteamsDataService::countNextMatches($ws, $db, 15));
	}

	public function testCountNextMatchesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, NationalteamsDataService::countNextMatches($ws, $db, 15));
	}

	public function testCountSimulatedMatchesReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '20']]));
		$this->assertSame('20', NationalteamsDataService::countSimulatedMatches($ws, $db, 15));
	}

	public function testCountSimulatedMatchesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, NationalteamsDataService::countSimulatedMatches($ws, $db, 15));
	}

	public function testGetNextMatchesDelegatesToMatchesDataService(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'type' => 'Ligaspiel', 'cup_name' => '', 'cup_round' => '', 'home_noformation' => '0', 'guest_noformation' => '0',
			 'home_team' => 'Home', 'home_team_picture' => '', 'home_id' => '5', 'home_user_id' => '', 'home_user_nick' => '',
			 'home_user_email' => '', 'home_user_picture' => '', 'guest_team' => 'Guest', 'guest_team_picture' => '', 'guest_id' => '6',
			 'guest_user_id' => '', 'guest_user_nick' => '', 'guest_user_email' => '', 'guest_user_picture' => '',
			 'home_goals' => '0', 'guest_goals' => '0', 'simulated' => '0', 'minutes' => '0', 'date' => '100'],
		]));
		$matches = NationalteamsDataService::getNextMatches($ws, $db, 15, 0, 10);
		$this->assertCount(1, $matches);
		$this->assertSame('Home', $matches[0]['home_team']);
	}

	public function testGetSimulatedMatchesDelegatesToMatchesDataService(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '2', 'type' => 'Ligaspiel', 'cup_name' => '', 'cup_round' => '', 'home_noformation' => '0', 'guest_noformation' => '0',
			 'home_team' => 'Home', 'home_team_picture' => '', 'home_id' => '5', 'home_user_id' => '', 'home_user_nick' => '',
			 'home_user_email' => '', 'home_user_picture' => '', 'guest_team' => 'Guest', 'guest_team_picture' => '', 'guest_id' => '6',
			 'guest_user_id' => '', 'guest_user_nick' => '', 'guest_user_email' => '', 'guest_user_picture' => '',
			 'home_goals' => '2', 'guest_goals' => '1', 'simulated' => '1', 'minutes' => '90', 'date' => '100'],
		]));
		$matches = NationalteamsDataService::getSimulatedMatches($ws, $db, 15, 0, 10);
		$this->assertCount(1, $matches);
		$this->assertSame('1', $matches[0]['simulated']);
	}
}
