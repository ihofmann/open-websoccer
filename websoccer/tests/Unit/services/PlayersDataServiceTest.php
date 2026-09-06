<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PlayersDataService.
 */
final class PlayersDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(array_merge([
			'db_prefix' => 'ws',
			'players_aging' => 'age',
			'transfermarket_computed_marketvalue' => '0',
		], $config));
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	private function basePlayerRow(array $overrides = []): array {
		// Keys must match the column ALIASES the service maps to, since the
		// MockDbResult returns rows verbatim.
		return array_merge([
			'id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'matches_injured' => '0',
			'position' => 'Torwart', 'position_main' => 'T', 'position_second' => '',
			'strength' => '80', 'strength_technique' => '70', 'strength_technic' => '70', 'strength_stamina' => '60',
			'strength_freshness' => '50', 'strength_satisfaction' => '40',
			'transfermarket' => '0', 'player_nationality' => 'Deutschland', 'picture' => '',
			'st_goals' => '0', 'st_matches' => '10', 'st_cards_yellow' => '0', 'st_cards_yellow_red' => '0', 'st_cards_red' => '0',
			'marketvalue' => '5000', 'age' => '25', 'matches_blocked' => '0',
			'contract_salary' => '1000', 'contract_matches' => '30', 'contract_goalbonus' => '100',
			'transfer_start' => '0', 'transfer_deadline' => '0', 'min_bid' => '0',
			'team_id' => '5', 'team_name' => 'FC Test',
		], $overrides);
	}

	public function testGetPlayersOfTeamByPositionGroupsByConvertedPosition(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->basePlayerRow(['id' => '1', 'position' => 'Torwart']),
			$this->basePlayerRow(['id' => '2', 'position' => 'Abwehr', 'position_main' => 'IV']),
		]));
		$players = PlayersDataService::getPlayersOfTeamByPosition($ws, $db, 5);
		$this->assertCount(1, $players['goaly']);
		$this->assertCount(1, $players['defense']);
		$this->assertSame('5000', $players['goaly'][0]['marketvalue']);
		$this->assertSame('Deutschland', $players['goaly'][0]['player_nationality_filename']);
	}

	public function testGetPlayersOfTeamByPositionWithCupBlocksColumn(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->basePlayerRow(['id' => '1', 'matches_blocked' => '2']),
		]));
		$players = PlayersDataService::getPlayersOfTeamByPosition($ws, $db, 5, 'ASC', true);
		$this->assertSame('2', $players['goaly'][0]['matches_blocked']);
	}

	public function testGetPlayersOfTeamByPositionReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], PlayersDataService::getPlayersOfTeamByPosition($ws, $db, 5));
	}

	public function testGetPlayersOfTeamByIdReturnsKeyedById(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->basePlayerRow(['id' => '1', 'position' => 'Torwart']),
			$this->basePlayerRow(['id' => '2', 'position' => 'Sturm', 'position_main' => 'MS']),
		]));
		$players = PlayersDataService::getPlayersOfTeamById($ws, $db, 5);
		$this->assertArrayHasKey('1', $players);
		$this->assertArrayHasKey('2', $players);
		$this->assertSame('goaly', $players['1']['position']);
		$this->assertSame('striker', $players['2']['position']);
	}

	public function testGetPlayersOfTeamByIdNationalteamUsesNationalplayerJoin(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->basePlayerRow(['id' => '1', 'matches_blocked' => '1']),
		]));
		$players = PlayersDataService::getPlayersOfTeamById($ws, $db, 15, true);
		$this->assertSame('1', $players['1']['matches_blocked']);
	}

	public function testGetPlayersOfTeamByIdReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], PlayersDataService::getPlayersOfTeamById($ws, $db, 5));
	}

	public function testGetPlayersOnTransferListReturnsPlayersWithHighestBid(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// 1st querySelect: transfer list; 2nd querySelect: getHighestBidForPlayer.
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([
				$this->basePlayerRow(['id' => '1', 'transfermarket' => '1', 'transfer_start' => '100', 'transfer_deadline' => '200', 'min_bid' => '1000', 'position' => 'Torwart']),
			]),
			$this->dbResult([['bid_id' => '9', 'amount' => '5000', 'user_name' => 'amy']])
		);
		$players = PlayersDataService::getPlayersOnTransferList($ws, $db, 0, 10);
		$this->assertCount(1, $players);
		$this->assertSame('5000', $players[0]['highestbid']['amount']);
	}

	public function testGetPlayersOnTransferListWithPositionFilterReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], PlayersDataService::getPlayersOnTransferList($ws, $db, 0, 10, 'Torwart'));
	}

	public function testCountPlayersOnTransferListReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '8']]));
		$this->assertSame('8', PlayersDataService::countPlayersOnTransferList($ws, $db));
	}

	public function testCountPlayersOnTransferListReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, PlayersDataService::countPlayersOnTransferList($ws, $db));
	}

	public function testGetPlayerByIdReturnsPlayerWhenFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'player_id' => '1', 'player_firstname' => 'Joe', 'player_lastname' => 'X', 'player_pseudonym' => '',
			'player_position' => 'Torwart', 'player_position_main' => 'T', 'player_position_second' => '',
			'player_birthday' => '2000-01-01', 'player_nationality' => 'Deutschland', 'player_picture' => '',
			'player_age' => '25', 'player_matches_injured' => '0', 'player_matches_blocked' => '0',
			'player_matches_blocked_cups' => '0', 'player_matches_blocked_nationalteam' => '0',
			'player_contract_salary' => '1000', 'player_contract_matches' => '30', 'player_contract_goalbonus' => '100',
			'player_strength' => '80', 'player_strength_technique' => '70', 'player_strength_stamina' => '60',
			'player_strength_freshness' => '50', 'player_strength_satisfaction' => '40',
			'player_season_goals' => '0', 'player_season_assists' => '0', 'player_season_matches' => '10',
			'player_season_yellow' => '0', 'player_season_yellow_red' => '0', 'player_season_red' => '0',
			'player_total_goals' => '0', 'player_total_assists' => '0', 'player_total_matches' => '10',
			'player_total_yellow' => '0', 'player_total_yellow_red' => '0', 'player_total_red' => '0',
			'player_transfermarket' => '0', 'player_marketvalue' => '5000', 'transfer_start' => '0', 'transfer_end' => '0',
			'transfer_min_bid' => '0', 'player_history' => '', 'player_unsellable' => '0',
			'lending_owner_id' => '', 'lending_owner_name' => null, 'lending_fee' => '0', 'lending_matches' => '0',
			'team_id' => '5', 'team_name' => 'FC Test', 'team_budget' => '1000000', 'team_user_id' => '7',
			'matches_info' => '2.5;3',
		]]);
		$player = PlayersDataService::getPlayerById($ws, $db, 1);
		$this->assertSame('Joe', $player['player_firstname']);
		$this->assertSame('goaly', $player['player_position']);
		$this->assertSame('5000', $player['player_marketvalue']);
		$this->assertSame(2.5, $player['player_avg_grade']);
		$this->assertSame('3', $player['player_assists']);
	}

	public function testGetPlayerByIdReturnsEmptyWhenNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->assertSame([], PlayersDataService::getPlayerById($ws, $db, 999));
	}

	public function testGetPlayerByIdHandlesMissingAssistsInfo(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'player_id' => '1', 'player_firstname' => 'Joe', 'player_lastname' => 'X', 'player_pseudonym' => '',
			'player_position' => 'Sturm', 'player_position_main' => 'MS', 'player_position_second' => '',
			'player_birthday' => '2000-01-01', 'player_nationality' => 'Deutschland', 'player_picture' => '',
			'player_age' => '25', 'player_matches_injured' => '0', 'player_matches_blocked' => '0',
			'player_matches_blocked_cups' => '0', 'player_matches_blocked_nationalteam' => '0',
			'player_contract_salary' => '1000', 'player_contract_matches' => '30', 'player_contract_goalbonus' => '100',
			'player_strength' => '80', 'player_strength_technique' => '70', 'player_strength_stamina' => '60',
			'player_strength_freshness' => '50', 'player_strength_satisfaction' => '40',
			'player_season_goals' => '0', 'player_season_assists' => '0', 'player_season_matches' => '0',
			'player_season_yellow' => '0', 'player_season_yellow_red' => '0', 'player_season_red' => '0',
			'player_total_goals' => '0', 'player_total_assists' => '0', 'player_total_matches' => '0',
			'player_total_yellow' => '0', 'player_total_yellow_red' => '0', 'player_total_red' => '0',
			'player_transfermarket' => '0', 'player_marketvalue' => '5000', 'transfer_start' => '0', 'transfer_end' => '0',
			'transfer_min_bid' => '0', 'player_history' => '', 'player_unsellable' => '0',
			'lending_owner_id' => '', 'lending_owner_name' => null, 'lending_fee' => '0', 'lending_matches' => '0',
			'team_id' => '5', 'team_name' => 'FC Test', 'team_budget' => '1000000', 'team_user_id' => '7',
			// only a grade, no assists segment -> assists defaults to 0.
			'matches_info' => '0',
		]]);
		$player = PlayersDataService::getPlayerById($ws, $db, 1);
		$this->assertSame(0.0, $player['player_avg_grade']);
		$this->assertSame(0, $player['player_assists']);
	}

	public function testGetPlayerByIdHandlesNullMatchesInfo(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'player_id' => '1', 'player_firstname' => 'Joe', 'player_lastname' => 'X', 'player_pseudonym' => '',
			'player_position' => 'Sturm', 'player_position_main' => 'MS', 'player_position_second' => '',
			'player_birthday' => '2000-01-01', 'player_nationality' => 'Deutschland', 'player_picture' => '',
			'player_age' => '25', 'player_matches_injured' => '0', 'player_matches_blocked' => '0',
			'player_matches_blocked_cups' => '0', 'player_matches_blocked_nationalteam' => '0',
			'player_contract_salary' => '1000', 'player_contract_matches' => '30', 'player_contract_goalbonus' => '100',
			'player_strength' => '80', 'player_strength_technique' => '70', 'player_strength_stamina' => '60',
			'player_strength_freshness' => '50', 'player_strength_satisfaction' => '40',
			'player_season_goals' => '0', 'player_season_assists' => '0', 'player_season_matches' => '0',
			'player_season_yellow' => '0', 'player_season_yellow_red' => '0', 'player_season_red' => '0',
			'player_total_goals' => '0', 'player_total_assists' => '0', 'player_total_matches' => '0',
			'player_total_yellow' => '0', 'player_total_yellow_red' => '0', 'player_total_red' => '0',
			'player_transfermarket' => '0', 'player_marketvalue' => '5000', 'transfer_start' => '0', 'transfer_end' => '0',
			'transfer_min_bid' => '0', 'player_history' => '', 'player_unsellable' => '0',
			'lending_owner_id' => '', 'lending_owner_name' => null, 'lending_fee' => '0', 'lending_matches' => '0',
			'team_id' => '5', 'team_name' => 'FC Test', 'team_budget' => '1000000', 'team_user_id' => '7',
			'matches_info' => null,
		]]);

		$player = PlayersDataService::getPlayerById($ws, $db, 1);

		$this->assertSame(0, $player['player_avg_grade']);
		$this->assertSame(0, $player['player_assists']);
	}

	public function testGetTopStrikersReturnsPlayers(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'goals' => '10', 'matches' => '5', 'transfermarket' => '0', 'team_id' => '5', 'team_name' => 'FC Test'],
		]));
		$players = PlayersDataService::getTopStrikers($ws, $db, 20);
		$this->assertCount(1, $players);
		$this->assertSame('10', $players[0]['goals']);
	}

	public function testGetTopStrikersWithLeagueFilterReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], PlayersDataService::getTopStrikers($ws, $db, 20, 3));
	}

	public function testGetTopScorersReturnsPlayersWithScore(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'goals' => '8', 'assists' => '5', 'matches' => '10', 'score' => '13', 'transfermarket' => '0', 'team_id' => '5', 'team_name' => 'FC Test'],
		]));
		$players = PlayersDataService::getTopScorers($ws, $db, 20, 3);
		$this->assertCount(1, $players);
		$this->assertSame('13', $players[0]['score']);
	}

	public function testGetTopScorersReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], PlayersDataService::getTopScorers($ws, $db));
	}

	public function testFindPlayersReturnsPlayersWithConvertedPosition(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'firstname' => 'Joe', 'lastname' => 'X', 'pseudonym' => '', 'position' => 'Torwart',
			 'position_main' => 'T', 'position_second' => '', 'transfermarket' => '0', 'unsellable' => '0',
			 'strength' => '80', 'strength_technique' => '70', 'strength_stamina' => '60', 'strength_freshness' => '50',
			 'strength_satisfaction' => '40', 'contract_salary' => '1000', 'contract_matches' => '30',
			 'lending_owner_id' => '', 'lending_fee' => '0', 'lending_matches' => '0', 'team_id' => '5', 'team_name' => 'FC Test'],
		]));
		$players = PlayersDataService::findPlayers($ws, $db, 'Joe', null, null, null, null, false, 0, 10);
		$this->assertCount(1, $players);
		$this->assertSame('goaly', $players[0]['position']);
	}

	public function testFindPlayersReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], PlayersDataService::findPlayers($ws, $db, null, null, null, null, null, false, 0, 10));
	}

	public function testFindPlayersCountReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '15']]));
		$this->assertSame('15', PlayersDataService::findPlayersCount($ws, $db, null, null, null, null, null, false));
	}

	public function testFindPlayersCountReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, PlayersDataService::findPlayersCount($ws, $db, null, null, null, null, null, false));
	}

	public function testConvertPositionReturnsExpectedIds(): void {
		$this->assertSame('goaly', PlayersDataService::_convertPosition('Torwart'));
		$this->assertSame('defense', PlayersDataService::_convertPosition('Abwehr'));
		$this->assertSame('midfield', PlayersDataService::_convertPosition('Mittelfeld'));
		$this->assertSame('striker', PlayersDataService::_convertPosition('Sturm'));
		$this->assertSame('striker', PlayersDataService::_convertPosition('AnythingElse'));
	}

	public function testGetMarketValueReturnsDbValueWhenComputedDisabled(): void {
		$ws = $this->makeWebsoccer(['transfermarket_computed_marketvalue' => '0']);
		$player = ['player_marketvalue' => '5000', 'player_strength' => 80, 'player_strength_technique' => 70,
			'player_strength_stamina' => 60, 'player_strength_freshness' => 50, 'player_strength_satisfaction' => 40];
		$this->assertSame('5000', PlayersDataService::getMarketValue($ws, $player));
	}

	public function testGetMarketValueReturnsComputedValueWhenEnabled(): void {
		$ws = $this->makeWebsoccer([
			'transfermarket_computed_marketvalue' => '1',
			'sim_weight_strength' => 1.0,
			'sim_weight_strengthTech' => 1.0,
			'sim_weight_strengthStamina' => 1.0,
			'sim_weight_strengthFreshness' => 1.0,
			'sim_weight_strengthSatisfaction' => 1.0,
			'transfermarket_value_per_strength' => 1000,
		]);
		$player = ['player_marketvalue' => '5000', 'player_strength' => 80, 'player_strength_technique' => 70,
			'player_strength_stamina' => 60, 'player_strength_freshness' => 50, 'player_strength_satisfaction' => 40];
		// average = (80+70+60+50+40)/5 = 60; 60 * 1000 = 60000.
		$this->assertSame(60000.0, PlayersDataService::getMarketValue($ws, $player));
	}

	public function testGetFlagFilenameReturnsInputForEmptyString(): void {
		$this->assertSame('', PlayersDataService::getFlagFilename(''));
	}

	public function testGetFlagFilenameReturnsNationalityForAscii(): void {
		$this->assertSame('England', PlayersDataService::getFlagFilename('England'));
	}
}
