<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for MatchesDataService.
 */
final class MatchesDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(array_merge([
			'db_prefix' => 'ws',
			'gravatar_enable' => '0',
			'context_root' => '/ws',
			'players_aging' => 'age',
		], $config));
	}

	private function userWithId(int $id): \User {
		return $this->makeUser(['id' => $id]);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetNextMatchesReturnsMatchesWithConvertedType(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['match_id' => '1', 'match_date' => '100', 'match_type' => 'Ligaspiel', 'match_home_id' => '5', 'match_home_name' => 'Home', 'match_guest_id' => '6', 'match_guest_name' => 'Guest'],
		]));
		$matches = MatchesDataService::getNextMatches($ws, $db, 5, 10);
		$this->assertCount(1, $matches);
		$this->assertSame('league', $matches[0]['match_type']);
	}

	public function testGetNextMatchesReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], MatchesDataService::getNextMatches($ws, $db, 5, 10));
	}

	public function testGetNextMatchReturnsMatchWhenFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '1', 'match_date' => '100', 'match_type' => 'Pokalspiel',
			'match_home_id' => '5', 'match_home_name' => 'Home', 'match_home_formation_id' => '11',
			'match_guest_id' => '6', 'match_guest_name' => 'Guest', 'match_guest_formation_id' => null,
		]]);
		$match = MatchesDataService::getNextMatch($ws, $db, 5);
		$this->assertSame('cup', $match['match_type']);
		$this->assertSame('Home', $match['match_home_name']);
	}

	public function testGetNextMatchReturnsEmptyArrayWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], MatchesDataService::getNextMatch($ws, $db, 5));
	}

	public function testGetLiveMatchReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getUser')->willReturn($this->userWithId(7));
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], MatchesDataService::getLiveMatch($ws, $db));
	}

	public function testGetLiveMatchReturnsMatchWhenFound(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getUser')->willReturn($this->userWithId(7));
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '2', 'match_date' => '200', 'match_type' => 'Freundschaft',
			'match_home_id' => '5', 'match_home_name' => 'Home', 'match_guest_id' => '6', 'match_guest_name' => 'Guest',
		]]);
		$match = MatchesDataService::getLiveMatch($ws, $db);
		$this->assertSame('friendly', $match['match_type']);
	}

	public function testGetMatchByIdReturnsMatchWithConvertedType(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '1', 'match_date' => '100', 'match_type' => 'Ligaspiel',
			'match_home_id' => '5', 'match_home_name' => 'Home', 'match_home_nationalteam' => '0', 'match_home_picture' => '',
			'match_guest_id' => '6', 'match_guest_name' => 'Guest', 'match_guest_nationalteam' => '0', 'match_guest_picture' => '',
			'match_cup_name' => '', 'match_cup_round' => '', 'match_matchday' => '1', 'match_season_id' => '1',
			'match_simulated' => '1', 'match_goals_home' => '2', 'match_goals_guest' => '1', 'match_deprecated_report' => '',
			'match_minutes' => '90', 'match_home_noformation' => '0', 'match_guest_noformation' => '0', 'match_audience' => '1000',
			'match_soldout' => '0', 'match_penalty_enabled' => '0', 'match_home_offensive' => '0', 'match_guest_offensive' => '0',
			'match_home_longpasses' => '0', 'match_guest_longpasses' => '0', 'match_home_counterattacks' => '0', 'match_guest_counterattacks' => '0',
			'match_stadium_name' => 'Arena',
		]]);
		$match = MatchesDataService::getMatchById($ws, $db, 1);
		$this->assertSame('league', $match['match_type']);
		$this->assertSame('Arena', $match['match_stadium_name']);
	}

	public function testGetMatchByIdReturnsEmptyWhenNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], MatchesDataService::getMatchById($ws, $db, 999));
	}

	public function testGetMatchByIdWithSeasonInfo(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '1', 'match_date' => '100', 'match_type' => 'Pokalspiel',
			'match_home_id' => '5', 'match_home_name' => 'Home', 'match_home_nationalteam' => '0', 'match_home_picture' => '',
			'match_guest_id' => '6', 'match_guest_name' => 'Guest', 'match_guest_nationalteam' => '0', 'match_guest_picture' => '',
			'match_cup_name' => 'Cup', 'match_cup_round' => 'Round 1', 'match_matchday' => '0', 'match_season_id' => '1',
			'match_simulated' => '0', 'match_goals_home' => '0', 'match_goals_guest' => '0', 'match_deprecated_report' => '',
			'match_minutes' => '0', 'match_home_noformation' => '0', 'match_guest_noformation' => '0', 'match_audience' => '0',
			'match_soldout' => '0', 'match_penalty_enabled' => '0', 'match_home_offensive' => '0', 'match_guest_offensive' => '0',
			'match_home_longpasses' => '0', 'match_guest_longpasses' => '0', 'match_home_counterattacks' => '0', 'match_guest_counterattacks' => '0',
			'match_stadium_name' => 'Arena', 'match_season_name' => 'Season 1', 'match_league_id' => '3',
		]]);
		$match = MatchesDataService::getMatchById($ws, $db, 1, true, true);
		$this->assertSame('Season 1', $match['match_season_name']);
		$this->assertSame('cup', $match['match_type']);
	}

	public function testGetMatchSubstitutionsByIdReturnsRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '1', 'match_home_id' => '5', 'match_guest_id' => '6', 'match_simulated' => '1', 'match_minutes' => '90',
			'match_home_offensive' => '1', 'match_home_offensive_changed' => '0', 'match_home_longpasses' => '0',
			'match_home_counterattacks' => '0', 'match_home_freekickplayer' => '3',
			'match_guest_offensive_changed' => '0', 'match_guest_offensive' => '0', 'match_guest_longpasses' => '0',
			'match_guest_counterattacks' => '0', 'match_guest_freekickplayer' => '4',
		]]);
		$subs = MatchesDataService::getMatchSubstitutionsById($ws, $db, 1);
		$this->assertSame('1', $subs['match_home_offensive']);
	}

	public function testGetMatchSubstitutionsByIdReturnsEmptyWhenNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], MatchesDataService::getMatchSubstitutionsById($ws, $db, 999));
	}

	public function testGetLastMatchReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getUser')->willReturn($this->userWithId(7));
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], MatchesDataService::getLastMatch($ws, $db));
	}

	public function testGetLiveMatchByTeamReturnsMatchWhenFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '3', 'match_date' => '300', 'match_type' => 'Ligaspiel',
			'match_home_id' => '5', 'match_home_name' => 'Home', 'match_guest_id' => '6', 'match_guest_name' => 'Guest',
			'match_goals_home' => '1', 'match_goals_guest' => '0',
		]]);
		$match = MatchesDataService::getLiveMatchByTeam($ws, $db, 5);
		$this->assertSame('league', $match['match_type']);
	}

	public function testGetPreviousMatchesReturnsMatches(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'home_team' => 'Home', 'guest_team' => 'Guest', 'home_goals' => '2', 'guest_goals' => '1'],
		]));
		$matchinfo = ['match_home_id' => 5, 'match_guest_id' => 6];
		$matches = MatchesDataService::getPreviousMatches($matchinfo, $ws, $db);
		$this->assertCount(1, $matches);
		$this->assertSame('Home', $matches[0]['home_team']);
	}

	public function testGetCupRoundsByCupnameGroupsByCup(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['cup' => 'FA Cup', 'round' => 'Round 1', 'round_date' => '100'],
			['cup' => 'FA Cup', 'round' => 'Round 2', 'round_date' => '200'],
			['cup' => 'League Cup', 'round' => 'Final', 'round_date' => '300'],
		]));
		$rounds = MatchesDataService::getCupRoundsByCupname($ws, $db);
		$this->assertCount(2, $rounds['FA Cup']);
		$this->assertSame('Final', $rounds['League Cup'][0]);
	}

	public function testGetMatchesByMatchdayReturnsMatches(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'type' => 'Ligaspiel', 'cup_name' => '', 'cup_round' => '', 'home_noformation' => '0', 'guest_noformation' => '0',
			 'home_team' => 'Home', 'home_team_picture' => '', 'home_id' => '5', 'home_user_id' => '7', 'home_user_nick' => 'joe',
			 'home_user_email' => '', 'home_user_picture' => '', 'guest_team' => 'Guest', 'guest_team_picture' => '', 'guest_id' => '6',
			 'guest_user_id' => '8', 'guest_user_nick' => 'amy', 'guest_user_email' => '', 'guest_user_picture' => '',
			 'home_goals' => '2', 'guest_goals' => '1', 'simulated' => '1', 'minutes' => '90', 'date' => '100'],
		]));
		$matches = MatchesDataService::getMatchesByMatchday($ws, $db, 1, 5);
		$this->assertCount(1, $matches);
		$this->assertSame('Home', $matches[0]['home_team']);
	}

	public function testGetLatestMatchesWithIgnoreFriendlies(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'type' => 'Ligaspiel', 'cup_name' => '', 'cup_round' => '', 'home_noformation' => '0', 'guest_noformation' => '0',
			 'home_team' => 'Home', 'home_team_picture' => '', 'home_id' => '5', 'home_user_id' => '', 'home_user_nick' => '',
			 'home_user_email' => '', 'home_user_picture' => '', 'guest_team' => 'Guest', 'guest_team_picture' => '', 'guest_id' => '6',
			 'guest_user_id' => '', 'guest_user_nick' => '', 'guest_user_email' => '', 'guest_user_picture' => '',
			 'home_goals' => '0', 'guest_goals' => '0', 'simulated' => '1', 'minutes' => '90', 'date' => '100'],
		]));
		$matches = MatchesDataService::getLatestMatches($ws, $db, 20, true);
		$this->assertCount(1, $matches);
	}

	public function testCountTodaysMatchesReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '5']]));
		$this->assertSame('5', MatchesDataService::countTodaysMatches($ws, $db));
	}

	public function testCountTodaysMatchesReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, MatchesDataService::countTodaysMatches($ws, $db));
	}

	public function testGetMatchdayNumberOfTeamReturnsNumber(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['matchday' => '7']]));
		$this->assertSame(7, MatchesDataService::getMatchdayNumberOfTeam($ws, $db, 5));
	}

	public function testGetMatchdayNumberOfTeamReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, MatchesDataService::getMatchdayNumberOfTeam($ws, $db, 5));
	}

	public function testGetMatchReportPlayerRecordsReturnsRows(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([
			['id' => '1', 'firstName' => 'Joe', 'lastName' => 'X', 'pseudonym' => '', 'position' => 'Torwart',
			 'position_main' => 'T', 'grade' => '2', 'goals' => '0', 'injured' => '0', 'blocked' => '0',
			 'yellowCards' => '0', 'redCard' => '0', 'playstatus' => 'Feld', 'minutesPlayed' => '90',
			 'assists' => '0', 'ballcontacts' => '10', 'wontackles' => '5', 'losttackles' => '2', 'shoots' => '1',
			 'passes_successed' => '20', 'passes_failed' => '3', 'age' => '25', 'strength' => '80'],
		]);
		$players = MatchesDataService::getMatchReportPlayerRecords($ws, $db, 1, 5);
		$this->assertCount(1, $players);
		$this->assertSame('Joe', $players[0]['firstName']);
	}

	public function testGetMatchPlayerRecordsByFieldGroupsFieldAndBench(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'matches_injured' => '0',
			 'position' => 'Torwart', 'position_main' => 'T', 'position_second' => '', 'strength' => '80',
			 'strength_technique' => '70', 'strength_stamina' => '60', 'strength_freshness' => '50',
			 'strength_satisfaction' => '40', 'player_nationality' => 'Deutschland', 'picture' => '',
			 'st_goals' => '5', 'st_matches' => '10', 'st_cards_yellow' => '1', 'st_cards_yellow_red' => '0',
			 'st_cards_red' => '0', 'match_record_id' => '1', 'match_position' => 'Torwart', 'match_position_main' => 'T',
			 'field' => '1', 'grade' => '2', 'age' => '25'],
			['id' => '2', 'firstname' => 'Amy', 'lastname' => 'Y', 'pseudonym' => '', 'matches_injured' => '0',
			 'position' => 'Abwehr', 'position_main' => 'IV', 'position_second' => '', 'strength' => '70',
			 'strength_technique' => '60', 'strength_stamina' => '60', 'strength_freshness' => '50',
			 'strength_satisfaction' => '40', 'player_nationality' => 'England', 'picture' => '',
			 'st_goals' => '0', 'st_matches' => '8', 'st_cards_yellow' => '0', 'st_cards_yellow_red' => '0',
			 'st_cards_red' => '0', 'match_record_id' => '2', 'match_position' => 'Abwehr', 'match_position_main' => 'IV',
			 'field' => 'Ersatzbank', 'grade' => '3', 'age' => '22'],
		]));
		$players = MatchesDataService::getMatchPlayerRecordsByField($ws, $db, 1, 5);
		$this->assertCount(1, $players['field']);
		$this->assertCount(1, $players['bench']);
		$this->assertSame('goaly', $players['field'][0]['position']);
		$this->assertSame('defense', $players['bench'][0]['position']);
	}

	public function testGetMatchReportMessagesReplacesPlayerPlaceholders(): void {
		$ws = $this->makeWebsoccer();
		$i18n = $this->mockI18n([]);
		$db = $this->dbSelect($this->dbResult([
			['report_id' => '1', 'minute' => '10', 'playerNames' => 'Joe;Amy', 'goals' => '1',
			 'message' => 'Goal by {sp1}!', 'type' => 'goal', 'active_home' => '1'],
		]));
		$messages = MatchesDataService::getMatchReportMessages($ws, $db, $i18n, 1);
		$this->assertCount(1, $messages);
		$this->assertSame('Goal by Joe!', $messages[0]['message']);
	}

	public function testGetMatchReportMessagesUsesI18nWhenKeyMatches(): void {
		$ws = $this->makeWebsoccer();
		$i18n = $this->mockI18n(['goal_scored' => 'Goal by {sp1} and {sp2}!']);
		$db = $this->dbSelect($this->dbResult([
			['report_id' => '1', 'minute' => '20', 'playerNames' => 'Joe;Amy', 'goals' => '2',
			 'message' => 'goal_scored', 'type' => 'goal', 'active_home' => '1'],
		]));
		$messages = MatchesDataService::getMatchReportMessages($ws, $db, $i18n, 1);
		$this->assertSame('Goal by Joe and Amy!', $messages[0]['message']);
	}

	public function testConvertLeagueTypeReturnsNullForUnknownType(): void {
		// Indirectly via getMatchById with an unknown type value.
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id' => '1', 'match_date' => '100', 'match_type' => 'UnknownType',
			'match_home_id' => '5', 'match_home_name' => 'Home', 'match_home_nationalteam' => '0', 'match_home_picture' => '',
			'match_guest_id' => '6', 'match_guest_name' => 'Guest', 'match_guest_nationalteam' => '0', 'match_guest_picture' => '',
			'match_cup_name' => '', 'match_cup_round' => '', 'match_matchday' => '1', 'match_season_id' => '1',
			'match_simulated' => '1', 'match_goals_home' => '0', 'match_goals_guest' => '0', 'match_deprecated_report' => '',
			'match_minutes' => '90', 'match_home_noformation' => '0', 'match_guest_noformation' => '0', 'match_audience' => '0',
			'match_soldout' => '0', 'match_penalty_enabled' => '0', 'match_home_offensive' => '0', 'match_guest_offensive' => '0',
			'match_home_longpasses' => '0', 'match_guest_longpasses' => '0', 'match_home_counterattacks' => '0', 'match_guest_counterattacks' => '0',
			'match_stadium_name' => 'Arena',
		]]);
		$match = MatchesDataService::getMatchById($ws, $db, 1);
		$this->assertNull($match['match_type']);
	}
}
