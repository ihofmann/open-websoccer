<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TeamsDataService.
 */
final class TeamsDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'gravatar_enable' => 0,
			'context_root' => '/soccer',
		]);
	}

	public function testGetTeamSummaryByIdReturnsNullForZeroId(): void {
		$db = $this->mockDb();
		$this->assertNull(TeamsDataService::getTeamSummaryById($this->ws, $db, 0));
	}

	public function testGetTeamSummaryByIdReturnsRow(): void {
		$row = ['team_id' => 7, 'team_name' => 'FC', 'team_budget' => 1000, 'team_picture' => 'x.png', 'user_id' => 5, 'team_league_name' => 'L1', 'team_league_id' => 1];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$row]);
		$this->assertSame($row, TeamsDataService::getTeamSummaryById($this->ws, $db, 7));
	}

	public function testGetTeamSummaryByIdReturnsEmptyArrayWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], TeamsDataService::getTeamSummaryById($this->ws, $db, 7));
	}

	public function testGetTeamByIdReturnsRowWithUserPicture(): void {
		$row = ['team_id' => 7, 'team_name' => 'FC', 'team_user_email' => 'a@b.com', 'team_user_picture' => 'p.jpg', 'team_deputyuser_email' => null, 'team_deputyuser_picture' => null];
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$row]);
		$team = TeamsDataService::getTeamById($this->ws, $db, 7);
		$this->assertSame('FC', $team['team_name']);
		$this->assertSame('/soccer/uploads/users/p.jpg', $team['user_picture']);
	}

	public function testGetTeamByIdReturnsEmptyArrayWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], TeamsDataService::getTeamById($this->ws, $db, 999));
	}

	public function testGetTableRankOfTeamReturnsRank(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['RNK' => '3']]));
		$this->assertSame(3, TeamsDataService::getTableRankOfTeam($this->ws, $db, 7));
	}

	public function testGetTableRankOfTeamReturnsZeroWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, TeamsDataService::getTableRankOfTeam($this->ws, $db, 7));
	}

	public function testGetTeamsOfLeagueOrderedByTableCriteriaReturnsList(): void {
		// prevent league history update (which would call executeQuery)
		$_SESSION['leaguehist'] = time() + 10000;
		$teams = [
			['id' => 1, 'name' => 'A', 'score' => 10, 'goals' => 5, 'goals_received' => 2, 'goals_diff' => 3, 'wins' => 3, 'defeats' => 1, 'draws' => 1, 'matches' => 5, 'picture' => '', 'user_id' => 0, 'user_name' => '', 'user_email' => '', 'user_picture' => '', 'previous_rank' => null],
			['id' => 2, 'name' => 'B', 'score' => 8, 'goals' => 4, 'goals_received' => 3, 'goals_diff' => 1, 'wins' => 2, 'defeats' => 2, 'draws' => 1, 'matches' => 5, 'picture' => '', 'user_id' => 0, 'user_name' => '', 'user_email' => '', 'user_picture' => '', 'previous_rank' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['id' => 1]]), // season
			$this->dbResult($teams)         // teams
		);
		$result = TeamsDataService::getTeamsOfLeagueOrderedByTableCriteria($this->ws, $db, 1);
		$this->assertCount(2, $result);
		$this->assertSame('A', $result[0]['name']);
	}

	public function testGetTeamsOfSeasonOrderedByTableCriteriaReturnsList(): void {
		$teams = [
			['id' => 1, 'name' => 'A', 'picture' => '', 'score' => 10, 'goals' => 5, 'goals_received' => 2, 'goals_diff' => 3, 'wins' => 3, 'draws' => 1, 'defeats' => 1, 'matches' => 5, 'user_id' => 0, 'user_name' => '', 'user_email' => '', 'user_picture' => ''],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($teams));
		$result = TeamsDataService::getTeamsOfSeasonOrderedByTableCriteria($this->ws, $db, 1, null);
		$this->assertCount(1, $result);
		$this->assertSame('A', $result[0]['name']);
	}

	public function testGetTeamsOfLeagueOrderedByAlltimeTableCriteriaReturnsList(): void {
		$teams = [
			['id' => 1, 'name' => 'A', 'picture' => '', 'score' => 20, 'goals' => 9, 'goals_received' => 4, 'goals_diff' => 5, 'wins' => 6, 'draws' => 2, 'defeats' => 2, 'matches' => 10, 'user_id' => 0, 'user_name' => '', 'user_email' => '', 'user_picture' => ''],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($teams));
		$result = TeamsDataService::getTeamsOfLeagueOrderedByAlltimeTableCriteria($this->ws, $db, 1);
		$this->assertCount(1, $result);
		$this->assertSame('A', $result[0]['name']);
	}

	public function testGetTeamsWithoutUserGroupsByCountry(): void {
		$rows = [
			['team_id' => 1, 'team_name' => 'A', 'team_budget' => 100, 'team_picture' => '', 'team_strength' => 50, 'league_id' => 1, 'league_name' => 'L1', 'league_country' => 'Germany', 'stadium_p_steh' => 100, 'stadium_p_sitz' => 50, 'stadium_p_haupt_steh' => 10, 'stadium_p_haupt_sitz' => 5, 'stadium_p_vip' => 2],
			['team_id' => 2, 'team_name' => 'B', 'team_budget' => 200, 'team_picture' => '', 'team_strength' => 60, 'league_id' => 2, 'league_name' => 'L2', 'league_country' => 'Austria', 'stadium_p_steh' => 100, 'stadium_p_sitz' => 50, 'stadium_p_haupt_steh' => 10, 'stadium_p_haupt_sitz' => 5, 'stadium_p_vip' => 2],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$teams = TeamsDataService::getTeamsWithoutUser($this->ws, $db);
		$this->assertArrayHasKey('Germany', $teams);
		$this->assertArrayHasKey('Austria', $teams);
		$this->assertSame('A', $teams['Germany'][0]['team_name']);
	}

	public function testGetTeamsWithoutUserReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TeamsDataService::getTeamsWithoutUser($this->ws, $db));
	}

	public function testCountTeamsWithoutManagerReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 5]]));
		$this->assertSame(5, TeamsDataService::countTeamsWithoutManager($this->ws, $db));
	}

	public function testCountTeamsWithoutManagerReturnsZeroWhenNoHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['foo' => 'bar']]));
		$this->assertSame(0, TeamsDataService::countTeamsWithoutManager($this->ws, $db));
	}

	public function testFindTeamNamesReturnsListOfNames(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['name' => 'FC A'], ['name' => 'FC B']]));
		$this->assertSame(['FC A', 'FC B'], TeamsDataService::findTeamNames($this->ws, $db, 'FC'));
	}

	public function testFindTeamNamesReturnsEmptyWhenNoMatch(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], TeamsDataService::findTeamNames($this->ws, $db, 'zz'));
	}

	public function testGetTeamSizeReturnsNumber(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['number' => 22]]));
		$this->assertSame(22, TeamsDataService::getTeamSize($this->ws, $db, 7));
	}

	public function testGetTeamSizeReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['number' => 0]]));
		$this->assertSame(0, TeamsDataService::getTeamSize($this->ws, $db, 7));
	}

	public function testGetTotalPlayersSalariesOfTeamReturnsSum(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['salary' => 5000]]));
		$this->assertSame(5000, TeamsDataService::getTotalPlayersSalariesOfTeam($this->ws, $db, 7));
	}

	public function testGetTotalPlayersSalariesOfTeamReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['salary' => 0]]));
		$this->assertSame(0, TeamsDataService::getTotalPlayersSalariesOfTeam($this->ws, $db, 7));
	}

	public function testGetTeamCaptainIdOfTeamReturnsId(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['captain_id' => 9]]));
		$this->assertSame(9, TeamsDataService::getTeamCaptainIdOfTeam($this->ws, $db, 7));
	}

	public function testGetTeamCaptainIdOfTeamReturnsZeroWhenNotSet(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['other' => 1]]));
		$this->assertSame(0, TeamsDataService::getTeamCaptainIdOfTeam($this->ws, $db, 7));
	}

	public function testValidateWhetherTeamHasEnoughBudgetForSalaryBidThrowsWhenBudgetTooLow(): void {
		$i18n = $this->mockI18n(['extend-contract_cannot_afford_offer' => 'Cannot afford offer.']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['salary_sum' => 100]]));
		$db->method('queryCachedSelect')->willReturn([['team_id' => 7, 'team_budget' => 200]]);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Cannot afford offer.');
		// minBudget = (100 + 50) * 2 = 300; 200 < 300 -> throws
		TeamsDataService::validateWhetherTeamHasEnoughBudgetForSalaryBid($this->ws, $db, $i18n, 7, 50);
	}

	public function testValidateWhetherTeamHasEnoughBudgetForSalaryBidPassesWhenBudgetSufficient(): void {
		$i18n = $this->mockI18n([]);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['salary_sum' => 100]]));
		$db->method('queryCachedSelect')->willReturn([['team_id' => 7, 'team_budget' => 500]]);
		// minBudget = 300; 500 >= 300 -> no exception
		TeamsDataService::validateWhetherTeamHasEnoughBudgetForSalaryBid($this->ws, $db, $i18n, 7, 50);
		$this->assertTrue(true);
	}
}
