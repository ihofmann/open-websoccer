<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthPlayersDataService.
 */
final class YouthPlayersDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'gravatar_enable' => 0,
			'context_root' => '/soccer',
			'youth_salary_per_strength' => 5,
			'youth_matchrequest_accept_hours_in_advance' => 24,
		]);
	}

	public function testGetYouthPlayerByIdReturnsPlayer(): void {
		$i18n = $this->mockI18n([]);
		$row = ['id' => 1, 'firstname' => 'A', 'lastname' => 'B', 'strength' => 50];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$row]);
		$this->assertSame($row, YouthPlayersDataService::getYouthPlayerById($this->ws, $db, $i18n, 1));
	}

	public function testGetYouthPlayerByIdThrowsWhenNotFound(): void {
		$i18n = $this->mockI18n(['error_page_not_found' => 'Page not found.']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Page not found.');
		YouthPlayersDataService::getYouthPlayerById($this->ws, $db, $i18n, 999);
	}

	public function testGetYouthPlayersOfTeamReturnsList(): void {
		$rows = [['id' => 1, 'firstname' => 'A'], ['id' => 2, 'firstname' => 'B']];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($rows);
		$this->assertSame($rows, YouthPlayersDataService::getYouthPlayersOfTeam($this->ws, $db, 7));
	}

	public function testGetYouthPlayersOfTeamReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], YouthPlayersDataService::getYouthPlayersOfTeam($this->ws, $db, 7));
	}

	public function testCountYouthPlayersOfTeamReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 6]]));
		$this->assertSame(6, YouthPlayersDataService::countYouthPlayersOfTeam($this->ws, $db, 7));
	}

	public function testCountYouthPlayersOfTeamReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthPlayersDataService::countYouthPlayersOfTeam($this->ws, $db, 7));
	}

	public function testComputeSalarySumReturnsStrengthSumTimesConfig(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['strengthsum' => 100]]));
		// 100 * 5 = 500
		$this->assertSame(500, YouthPlayersDataService::computeSalarySumOfYouthPlayersOfTeam($this->ws, $db, 7));
	}

	public function testComputeSalarySumReturnsZeroWhenNoPlayers(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthPlayersDataService::computeSalarySumOfYouthPlayersOfTeam($this->ws, $db, 7));
	}

	public function testGetYouthPlayersOfTeamByPositionGroupsByConvertedPosition(): void {
		$rows = [
			['id' => 1, 'position' => 'Torwart', 'nation' => 'England', 'lastname' => 'A', 'firstname' => 'A'],
			['id' => 2, 'position' => 'Abwehr', 'nation' => 'England', 'lastname' => 'B', 'firstname' => 'B'],
			['id' => 3, 'position' => 'Mittelfeld', 'nation' => 'England', 'lastname' => 'C', 'firstname' => 'C'],
			['id' => 4, 'position' => 'Sturm', 'nation' => 'England', 'lastname' => 'D', 'firstname' => 'D'],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$grouped = YouthPlayersDataService::getYouthPlayersOfTeamByPosition($this->ws, $db, 7);
		$this->assertArrayHasKey('goaly', $grouped);
		$this->assertArrayHasKey('defense', $grouped);
		$this->assertArrayHasKey('midfield', $grouped);
		$this->assertArrayHasKey('striker', $grouped);
		$this->assertSame('England', $grouped['goaly'][0]['player_nationality']);
		$this->assertSame('England', $grouped['goaly'][0]['player_nationality_filename']);
	}

	public function testGetYouthPlayersOfTeamByPositionReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], YouthPlayersDataService::getYouthPlayersOfTeamByPosition($this->ws, $db, 7));
	}

	public function testCountTransferableYouthPlayersReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 3]]));
		$this->assertSame(3, YouthPlayersDataService::countTransferableYouthPlayers($this->ws, $db));
	}

	public function testCountTransferableYouthPlayersWithPositionFilterReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 2]]));
		$this->assertSame(2, YouthPlayersDataService::countTransferableYouthPlayers($this->ws, $db, 'Torwart'));
	}

	public function testCountTransferableYouthPlayersReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthPlayersDataService::countTransferableYouthPlayers($this->ws, $db));
	}

	public function testGetTransferableYouthPlayersReturnsListWithPictureAndFlag(): void {
		$rows = [
			['player_id' => 1, 'firstname' => 'A', 'lastname' => 'B', 'position' => 'Torwart', 'nation' => 'England', 'transfer_fee' => 100, 'age' => 16, 'strength' => 50, 'st_matches' => 0, 'st_goals' => 0, 'st_assists' => 0, 'st_cards_yellow' => 0, 'st_cards_yellow_red' => 0, 'st_cards_red' => 0, 'team_id' => 1, 'team_name' => 'FC', 'team_picture' => '', 'user_id' => 5, 'user_nick' => 'u', 'user_email' => '', 'user_picture' => ''],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$players = YouthPlayersDataService::getTransferableYouthPlayers($this->ws, $db, null, 0, 10);
		$this->assertCount(1, $players);
		$this->assertSame('England', $players[0]['nation_flagfile']);
		// empty picture + gravatar disabled -> null
		$this->assertNull($players[0]['user_picture']);
	}

	public function testGetTransferableYouthPlayersReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], YouthPlayersDataService::getTransferableYouthPlayers($this->ws, $db, null, 0, 10));
	}

	public function testGetScoutsReturnsList(): void {
		$rows = [['id' => 1, 'name' => 'Scout A', 'expertise' => 5], ['id' => 2, 'name' => 'Scout B', 'expertise' => 3]];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertSame($rows, YouthPlayersDataService::getScouts($this->ws, $db));
	}

	public function testGetScoutsReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], YouthPlayersDataService::getScouts($this->ws, $db));
	}

	public function testGetScoutByIdReturnsRow(): void {
		$i18n = $this->mockI18n([]);
		$row = ['id' => 1, 'name' => 'Scout A', 'expertise' => 5];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, YouthPlayersDataService::getScoutById($this->ws, $db, $i18n, 1));
	}

	public function testGetScoutByIdThrowsWhenNotFound(): void {
		$i18n = $this->mockI18n(['youthteam_scouting_err_invalidscout' => 'Invalid scout.']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid scout.');
		YouthPlayersDataService::getScoutById($this->ws, $db, $i18n, 999);
	}

	public function testGetLastScoutingExecutionTimeReturnsTimestamp(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['scouting_last_execution' => 1700000000]]));
		$this->assertSame(1700000000, YouthPlayersDataService::getLastScoutingExecutionTime($this->ws, $db, 7));
	}

	public function testGetLastScoutingExecutionTimeReturnsZeroWhenNoTeam(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthPlayersDataService::getLastScoutingExecutionTime($this->ws, $db, 7));
	}

	public function testGetPossibleScoutingCountriesReturnsCountryFolders(): void {
		$countries = YouthPlayersDataService::getPossibleScoutingCountries();
		$this->assertContains('Deutschland', $countries);
		$this->assertContains('England', $countries);
		$this->assertContains('Italien', $countries);
		$this->assertContains('Spanien', $countries);
	}

	public function testCountMatchRequestsReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 4]]));
		$this->assertSame(4, YouthPlayersDataService::countMatchRequests($this->ws, $db));
	}

	public function testCountMatchRequestsReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthPlayersDataService::countMatchRequests($this->ws, $db));
	}

	public function testGetMatchRequestsReturnsList(): void {
		$rows = [
			['request_id' => 1, 'matchdate' => 100, 'reward' => 50, 'team_name' => 'FC', 'team_id' => 1, 'user_id' => 5, 'user_nick' => 'u', 'user_email' => '', 'user_picture' => ''],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$requests = YouthPlayersDataService::getMatchRequests($this->ws, $db, 0, 10);
		$this->assertCount(1, $requests);
		$this->assertSame('FC', $requests[0]['team_name']);
	}

	public function testGetMatchRequestsReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], YouthPlayersDataService::getMatchRequests($this->ws, $db, 0, 10));
	}

	public function testDeleteInvalidOpenMatchRequestsCallsQueryDelete(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())->method('queryDelete');
		YouthPlayersDataService::deleteInvalidOpenMatchRequests($this->ws, $db);
	}
}
