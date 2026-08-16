<?php

namespace OpenWebSoccer\Tests;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Shared factory helpers for action controller unit tests.
 *
 * Provides canned "rows" that mimic the output of the various data services
 * (which the actions call statically) and a flexible DbConnection mock whose
 * querySelect / queryCachedSelect results can be dispatched by table name.
 */
trait ActionTestHelpers {

	/**
	 * Builds a DbConnection mock whose queryCachedSelect / querySelect return
	 * canned rows, dispatched by matching a needle within the $fromTable
	 * argument. queryUpdate/queryInsert/queryDelete are no-ops (return null).
	 *
	 * @param array $cached   needle => rows[]  (for queryCachedSelect)
	 * @param array $select   needle => rows[]  (for querySelect)
	 */
	protected function makeDb(array $cached = [], array $select = []): \DbConnection&MockObject {
		// Sort needles by length (longest first) so that the most specific
		// table match wins when several needles are substrings of the same
		// fromTable (e.g. "_spieler AS P" vs. the "_verein AS C" join it contains).
		$cached = $this->sortNeedles($cached);
		$select = $this->sortNeedles($select);

		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable, $where, $params = null, $limit = null) use ($cached) {
				foreach ($cached as $needle => $rows) {
					if ($this->tableMatches($fromTable, $needle)) {
						return $rows;
					}
				}
				return [];
			}
		);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $where, $params = null, $limit = null) use ($select) {
				foreach ($select as $needle => $rows) {
					if ($this->tableMatches($fromTable, $needle)) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		return $db;
	}

	/**
	 * Returns the map sorted by key length, longest first.
	 */
	protected function sortNeedles(array $map): array {
		uksort($map, function ($a, $b) { return strlen($b) <=> strlen($a); });
		return $map;
	}

	/**
	 * Creates a WebSoccer mock identical to TestCaseBase::mockWebsoccer() but
	 * with a fixed "now" timestamp (which the base helper pins to time()).
	 *
	 * @param int   $now    fixed timestamp returned by getNowAsTimestamp().
	 * @param array $config setting id => value, returned by getConfig().
	 */
	protected function mockWebsoccerAt(int $now, array $config = []): \WebSoccer&MockObject {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn($now);
		return $ws;
	}

	/**
	 * Whether the fromTable string contains the needle (case-sensitive).
	 */
	protected function tableMatches(string $fromTable, string $needle): bool {
		return $needle === '' || strpos($fromTable, $needle) !== false;
	}

	/**
	 * Creates a logged-in user (id set) and primes the session so that
	 * User::getClubId() returns the club id without hitting the database.
	 */
	protected function makeLoggedUser(int $userId = 1, int $clubId = 1): \User {
		$_SESSION['clubid'] = $clubId;
		return $this->makeUser(['id' => $userId, 'username' => 'manager', 'email' => 'manager@example.com']);
	}

	/**
	 * Row mimicking PlayersDataService::getPlayerById() output (pre the service
	 * post-processing, which only rewrites a few keys). Supply overrides for the
	 * fields the action under test actually inspects.
	 */
	protected static function playerRow(array $overrides = []): array {
		return array_merge([
			'player_id' => 1,
			'player_firstname' => 'John',
			'player_lastname' => 'Doe',
			'player_pseudonym' => '',
			'player_position' => 'Torwart',
			'player_position_main' => 'T',
			'player_position_second' => '',
			'player_birthday' => '2000-01-01',
			'player_nationality' => 'Germany',
			'player_picture' => '',
			'player_age' => 24,
			'player_matches_injured' => 0,
			'player_matches_blocked' => 0,
			'player_matches_blocked_cups' => 0,
			'player_matches_blocked_nationalteam' => 0,
			'player_contract_salary' => 1000,
			'player_contract_matches' => 10,
			'player_contract_goalbonus' => 100,
			'player_strength' => 50,
			'player_strength_technique' => 50,
			'player_strength_stamina' => 50,
			'player_strength_freshness' => 50,
			'player_strength_satisfaction' => 80,
			'player_season_goals' => 0,
			'player_season_assists' => 0,
			'player_season_matches' => 0,
			'player_season_yellow' => 0,
			'player_season_yellow_red' => 0,
			'player_season_red' => 0,
			'player_total_goals' => 0,
			'player_total_assists' => 0,
			'player_total_matches' => 0,
			'player_total_yellow' => 0,
			'player_total_yellow_red' => 0,
			'player_total_red' => 0,
			'player_transfermarket' => 0,
			'player_marketvalue' => 100000,
			'transfer_start' => 0,
			'transfer_end' => 0,
			'transfer_min_bid' => 0,
			'player_history' => '',
			'player_unsellable' => 0,
			'lending_owner_id' => 0,
			'lending_owner_name' => '',
			'lending_fee' => 0,
			'lending_matches' => 0,
			'team_id' => 1,
			'team_name' => 'My Team',
			'team_budget' => 1000000,
			'team_user_id' => 1,
			'matches_info' => '0',
		], $overrides);
	}

	/**
	 * Row mimicking YouthPlayersDataService::getYouthPlayerById() output.
	 */
	protected static function youthPlayerRow(array $overrides = []): array {
		return array_merge([
			'id' => 1,
			'team_id' => 1,
			'firstname' => 'Young',
			'lastname' => 'Player',
			'age' => 17,
			'position' => 'Torwart',
			'nation' => 'Germany',
			'strength' => 40,
			'transfer_fee' => 0,
		], $overrides);
	}

	/**
	 * Row mimicking TeamsDataService::getTeamSummaryById() / getTeamById().
	 */
	protected static function teamRow(array $overrides = []): array {
		return array_merge([
			'team_id' => 1,
			'team_name' => 'My Team',
			'team_budget' => 1000000,
			'team_picture' => '',
			'user_id' => 1,
			'team_league_name' => 'Premier',
			'team_league_id' => 1,
			'captain_id' => 0,
		], $overrides);
	}

	/**
	 * Row mimicking StadiumsDataService::getStadiumByTeamId() output.
	 */
	protected static function stadiumRow(array $overrides = []): array {
		return array_merge([
			'stadium_id' => 1,
			'name' => 'My Stadium',
			'picture' => '',
			'places_stands' => 1000,
			'places_seats' => 500,
			'places_stands_grand' => 200,
			'places_seats_grand' => 100,
			'places_vip' => 50,
			'level_pitch' => 1,
			'level_videowall' => 1,
			'level_seatsquality' => 1,
			'level_vipquality' => 1,
		], $overrides);
	}
}
