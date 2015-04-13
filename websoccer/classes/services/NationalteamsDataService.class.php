<?php
/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it 
  and/or modify it under the terms of the 
  GNU Lesser General Public License 
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public 
  License along with OpenWebSoccer-Sim.  
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/

/**
 * Data service for national teams data.
 */
class NationalteamsDataService {

	/**
	 * Creates a new unseen notification about any event which shall catch the user's attention.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @return int ID of national team managed by the current user, or NULL if user does not manage a national team.
	 */
	public static function getNationalTeamManagedByCurrentUser(WebSoccer $websoccer, DbConnection $db) {
		
		$result = $db->queryCachedSelect("id", $websoccer->getConfig("db_prefix") . "_verein", 
				"user_id = %d AND nationalteam = '1'", $websoccer->getUser()->id, 1);
		if (count($result)) {
			return $result[0]["id"];
		}
		
		return NULL;
	}
	
	/**
	 * Provides players of specified national team, grouped by position.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $clubId ID of team.
	 * @param string $positionSort ASC|DESC
	 * @return array two dim array. first dim: Position name, second dim: List of players of this position.
	 */
	public static function getNationalPlayersOfTeamByPosition(WebSoccer $websoccer, DbConnection $db, $clubId, $positionSort = "ASC") {
		$columns = array(
				"P.id" => "id",
				"vorname" => "firstname",
				"nachname" => "lastname",
				"kunstname" => "pseudonym",
				"verletzt" => "matches_injured",
				"gesperrt_nationalteam" => "matches_blocked",
				"position" => "position",
				"position_main" => "position_main",
				"position_second" => "position_second",
				"w_staerke" => "strength",
				"w_technik" => "strength_technique",
				"w_kondition" => "strength_stamina",
				"w_frische" => "strength_freshness",
				"w_zufriedenheit" => "strength_satisfaction",
				"transfermarkt" => "transfermarket",
				"nation" => "player_nationality",
				"picture" => "picture",
				"P.sa_tore" => "st_goals",
				"P.sa_spiele" => "st_matches",
				"P.sa_karten_gelb" => "st_cards_yellow",
				"P.sa_karten_gelb_rot" => "st_cards_yellow_red",
				"P.sa_karten_rot" => "st_cards_red",
				"marktwert" => "marketvalue",
				"verein_id" => "team_id",
				"C.name" => "team_name"
		);
		
		if ($websoccer->getConfig('players_aging') == 'birthday') {
			$ageColumn = 'TIMESTAMPDIFF(YEAR,geburtstag,CURDATE())';
		} else {
			$ageColumn = 'age';
		}
		$columns[$ageColumn] = 'age';
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_spieler AS P";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_nationalplayer AS NP ON NP.player_id = P.id";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS C ON C.id = P.verein_id";
		$whereCondition = "P.status = 1 AND NP.team_id = %d ORDER BY position ". $positionSort . ", position_main ASC, nachname ASC, vorname ASC";
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $clubId, 50);
	
		$players = array();
		while ($player = $result->fetch_array()) {
			$player["position"] = PlayersDataService::_convertPosition($player["position"]);
			$player["player_nationality_filename"] = PlayersDataService::getFlagFilename($player["player_nationality"]);
			$player["marketvalue"] = PlayersDataService::getMarketValue($websoccer, $player, "");
			$players[$player["position"]][] = $player;
		}
		$result->free();
	
		return $players;
	}
	
	/**
	 * Count for players search.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param string $nationality Nationality as in DB.
	 * @param int $teamId ID of team.
	 * @param string $firstName first name.
	 * @param string $lastName surname or pseudonym.
	 * @param string $position position as in DB (Torwart|ABwehr|Mittelfeld|Sturm)
	 * @param string $mainPosition main position as in DB (T, LV, IV, ...)
	 * @return int number of found players.
	 */
	public static function findPlayersCount(WebSoccer $websoccer, DbConnection $db, $nationality, $teamId,
			$firstName, $lastName, $position, $mainPosition) {
		$columns = "COUNT(*) AS hits";
	
		$result = self::executeFindQuery($websoccer, $db, $columns, 1, $nationality, $teamId, $firstName, $lastName, $position, $mainPosition);
		$players = $result->fetch_array();
		$result->free();
	
		if (isset($players["hits"])) {
			return $players["hits"];
		}
	
		return 0;
	}
	
	/**
	 * Players search for national teams.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param string $nationality Nationality as in DB.
	 * @param int $teamId ID of team.
	 * @param string $firstName first name.
	 * @param string $lastName surname or pseudonym.
	 * @param string $position position as in DB (Torwart|ABwehr|Mittelfeld|Sturm)
	 * @param string $mainPosition main position as in DB (T, LV, IV, ...)
	 * @param int $startIndex fetch start index.
	 * @param int $entries_per_page number of entries per pae.
	 * @return array list of found players.
	 */
	public static function findPlayers(WebSoccer $websoccer, DbConnection $db, $nationality, $teamId,
			$firstName, $lastName, $position, $mainPosition, $startIndex, $entries_per_page) {
	
		$columns["P.id"] = "id";
		$columns["P.vorname"] = "firstname";
		$columns["P.nachname"] = "lastname";
		$columns["P.kunstname"] = "pseudonym";
	
		$columns["P.position"] = "position";
		$columns["P.position_main"] = "position_main";
		$columns["P.position_second"] = "position_second";
	
		$columns["P.w_staerke"] = "strength";
		$columns["P.w_technik"] = "strength_technique";
		$columns["P.w_kondition"] = "strength_stamina";
		$columns["P.w_frische"] = "strength_freshness";
		$columns["P.w_zufriedenheit"] = "strength_satisfaction";
	
		$columns["C.id"] = "team_id";
		$columns["C.name"] = "team_name";
	
		$limit = $startIndex .",". $entries_per_page;
		$result = self::executeFindQuery($websoccer, $db, $columns, $limit, $nationality, $teamId, $firstName, $lastName, $position, $mainPosition);
	
		$players = array();
		while ($player = $result->fetch_array()) {
			$player["position"] = PlayersDataService::_convertPosition($player["position"]);
			$players[] = $player;
				
		}
		$result->free();
	
		return $players;
	
	}
	
	private static function executeFindQuery(WebSoccer $websoccer, DbConnection $db, $columns, $limit,
			$nationality, $teamId, $firstName, $lastName, $position, $mainPosition) {
		$whereCondition = "P.status = 1 AND P.nation = '%s' AND P.verletzt = 0 AND P.id NOT IN (SELECT player_id FROM ". $websoccer->getConfig("db_prefix") . "_nationalplayer WHERE team_id = %d)";
	
		$parameters = array();
		$parameters[] = $nationality;
		$parameters[] = $teamId;
	
		if ($firstName != null) {
			$firstName = ucfirst($firstName);
			$whereCondition .= " AND P.vorname LIKE '%s%%'";
			$parameters[] = $firstName;
		}
	
		if ($lastName != null) {
			$lastName = ucfirst($lastName);
			$whereCondition .= " AND (P.nachname LIKE '%s%%' OR P.kunstname LIKE '%s%%')";
			$parameters[] = $lastName;
			$parameters[] = $lastName;
		}
	
		if ($position != null) {
			$whereCondition .= " AND P.position = '%s'";
			$parameters[] = $position;
		}
		
		if ($mainPosition != null) {
			$whereCondition .= " AND (P.position_main = '%s' OR P.position_second = '%s')";
			$parameters[] = $mainPosition;
			$parameters[] = $mainPosition;
		}
		
		$whereCondition .= " ORDER BY w_staerke DESC, w_technik DESC";

		$fromTable = $websoccer->getConfig("db_prefix") . "_spieler AS P";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS C ON C.id = P.verein_id";
	
		return $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $limit);
	}
	
	/**
	 * Provides number of future matches in that the specified team is involved in.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team
	 * @return int number of next matches to play.
	 */
	public static function countNextMatches(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$columns = "COUNT(*) AS hits";
		$fromTable = $websoccer->getConfig("db_prefix") . "_spiel";
		
		$result = $db->querySelect($columns, $fromTable, "(home_verein = %d OR gast_verein = %d) AND datum > %d", array($teamId, $teamId, $websoccer->getNowAsTimestamp()));
		$matches = $result->fetch_array();
		$result->free();
		
		if (isset($matches["hits"])) {
			return $matches["hits"];
		}
		
		return 0;
	}
	
	/**
	 * Provides next matches for specified team.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team.
	 * @param int $startIndex fetch index start.
	 * @param int $eps entries per page.
	 * @return array list of found matches.
	 */
	public static function getNextMatches(WebSoccer $websoccer, DbConnection $db, $teamId, $startIndex, $eps) {
		$whereCondition = "(home_verein = %d OR gast_verein = %d) AND datum > %d ORDER BY datum ASC";
		return MatchesDataService::getMatchesByCondition($websoccer, $db, $whereCondition, 
				array($teamId, $teamId, $websoccer->getNowAsTimestamp()), $startIndex . "," . $eps);
	}
	
	/**
	 * Provides number of simulates matches in that the specified team was involved in.
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team
	 * @return int number of simulated matches.
	 */
	public static function countSimulatedMatches(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$columns = "COUNT(*) AS hits";
		$fromTable = $websoccer->getConfig("db_prefix") . "_spiel";
	
		$result = $db->querySelect($columns, $fromTable, "(home_verein = %d OR gast_verein = %d) AND berechnet = '1'", array($teamId, $teamId));
		$matches = $result->fetch_array();
		$result->free();
	
		if (isset($matches["hits"])) {
			return $matches["hits"];
		}
	
		return 0;
	}
	
	/**
	 * Provides simulated matches of specified team.
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team.
	 * @param int $startIndex fetch index start.
	 * @param int $eps entries per page.
	 * @return array list of found matches.
	 */
	public static function getSimulatedMatches(WebSoccer $websoccer, DbConnection $db, $teamId, $startIndex, $eps) {
		$whereCondition = "(home_verein = %d OR gast_verein = %d) AND berechnet = '1' ORDER BY datum DESC";
		return MatchesDataService::getMatchesByCondition($websoccer, $db, $whereCondition,
				array($teamId, $teamId), $startIndex . "," . $eps);
	}
}
?>