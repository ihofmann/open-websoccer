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
 * Data service for youth players data management.
 */
class YouthPlayersDataService {
	
	/**
	 * Provides a youth player with specified ID. Throws an exception if player could not be found.
	 * Query is cached.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param int $playerId ID of player.
	 * @throws Exception if no player with specified ID could be found. Containing a Page-not-found message.
	 * @return array assoc. array of player data.
	 */
	public static function getYouthPlayerById(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $playerId) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer";
		
		$players = $db->queryCachedSelect("*", $fromTable, "id = %d", $playerId);
		
		if (!count($players)) {
			throw new Exception($i18n->getMessage("error_page_not_found"));
		}
		
		return $players[0];
	}
	
	/**
	 * Find all youth players of a specified team, ordered by position, last name and first name.
	 * Query is cached.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team.
	 * @return array array of youth players or empty array if team has no youth players.
	 */
	public static function getYouthPlayersOfTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer";
		$whereCondition = "team_id = %d ORDER BY position ASC, lastname ASC, firstname ASC";
		
		$players = $db->queryCachedSelect("*", $fromTable, $whereCondition, $teamId);
		
		return $players;
	}
	
	/**
	 * County outh players of a team.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team
	 * @return int number of youth players who belong to the specified team.
	 */
	public static function countYouthPlayersOfTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer";
	
	
		$result = $db->querySelect("COUNT(*) AS hits", $fromTable, "team_id = %d", $teamId);
		$players = $result->fetch_array();
		$result->free();
	
		if ($players) {
			return $players["hits"];
		}
	
		return 0;
	}
	
	/**
	 * Provides the salary to pay per match for all youth players of a specified team.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team
	 * @return int total sum of players salary
	 */
	public static function computeSalarySumOfYouthPlayersOfTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer";
	
	
		$result = $db->querySelect("SUM(strength) AS strengthsum", $fromTable, "team_id = %d", $teamId);
		$players = $result->fetch_array();
		$result->free();
	
		if ($players) {
			return $players["strengthsum"] * $websoccer->getConfig("youth_salary_per_strength");
		}
	
		return 0;
	}
	
	/**
	 * Provides youth players of team, grouped by position.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $clubId ID of team
	 * @param string $positionSort ASC or DESC
	 * @return array asoc. array of youth players, with key=converted position ID, value=list of players
	 */
	public static function getYouthPlayersOfTeamByPosition(WebSoccer $websoccer, DbConnection $db, $clubId, $positionSort = "ASC") {
		$columns = "*";
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer";
		$whereCondition = "team_id = %d ORDER BY position ". $positionSort . ", lastname ASC, firstname ASC";
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $clubId, 50);
	
		$players = array();
		while ($player = $result->fetch_array()) {
			$player["position"] = PlayersDataService::_convertPosition($player["position"]);
			$player["player_nationality"] = $player["nation"]; // make compliant with professional matches formation form
			$player["player_nationality_filename"] = PlayersDataService::getFlagFilename($player["nation"]);
			$players[$player["position"]][] = $player;
		}
		$result->free();
	
		return $players;
	}
	
	/**
	 * Provides number of youth players who are marked as transferable (i.e. having a transfer fee higher than 0).
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param string $positionFilter DB value of position to filter for.
	 * @return int number of tranferabl youth players.
	 */
	public static function countTransferableYouthPlayers(WebSoccer $websoccer, DbConnection $db, $positionFilter = NULL) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer";
		
		$parameters = "";
		$whereCondition = "transfer_fee > 0";
		
		if ($positionFilter != NULL) {
			$whereCondition .= " AND position = '%s'";
			$parameters = $positionFilter;
		}
		
		$result = $db->querySelect("COUNT(*) AS hits", $fromTable, $whereCondition, $parameters);
		$players = $result->fetch_array();
		$result->free();
		
		if ($players) {
			return $players["hits"];
		}
		
		return 0;
	}
	
	/**
	 * Provides youth players who are transferable.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param string|NULL $positionFilter DB value of position to filter for.
	 * @param int $startIndex fetch start index.
	 * @param int $entries_per_page Number of items to fetch.
	 * @return array Array of players or empty array if no youth players found.
	 */
	public static function getTransferableYouthPlayers(WebSoccer $websoccer, DbConnection $db, $positionFilter = NULL,
			$startIndex, $entries_per_page) {
		
		$columns = array(
				"P.id" => "player_id",
				"P.firstname" => "firstname",
				"P.lastname" => "lastname",
				"P.position" => "position",
				"P.nation" => "nation",
				"P.transfer_fee" => "transfer_fee",
				"P.age" => "age",
				"P.strength" => "strength",
				"P.st_matches" => "st_matches",
				"P.st_goals" => "st_goals",
				"P.st_assists" => "st_assists",
				"P.st_cards_yellow" => "st_cards_yellow",
				"P.st_cards_yellow_red" => "st_cards_yellow_red",
				"P.st_cards_red" => "st_cards_red",
				"P.team_id" => "team_id",
				"C.name" => "team_name",
				"C.bild" => "team_picture",
				"C.user_id" => "user_id",
				"U.nick" => "user_nick",
				"U.email" => "user_email",
				"U.picture" => "user_picture"
				);
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthplayer AS P";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS C ON C.id = P.team_id";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_user AS U ON U.id = C.user_id";
		
		$parameters = "";
		$whereCondition = "P.transfer_fee > 0";
		
		if ($positionFilter != NULL) {
			$whereCondition .= " AND P.position = '%s'";
			$parameters = $positionFilter;
		}
		
		$whereCondition .= " ORDER BY P.strength DESC";
		
		$players = array();
		
		$limit = $startIndex .",". $entries_per_page;
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $limit);
		while ($player = $result->fetch_array()) {
			$player["user_picture"] = UsersDataService::getUserProfilePicture($websoccer, $player["user_picture"], $player["user_email"], 20);
			$player["nation_flagfile"] = PlayersDataService::getFlagFilename($player["nation"]);
			$players[] = $player;
		}
		$result->free();
		
		return $players;
	}
	
	/**
	 * Provides a list of all available youth scouts.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param string $sortColumns ORDER BY part of query.
	 * @return array array of scouts or empty array if no scouts available.
	 */
	public static function getScouts(WebSoccer $websoccer, DbConnection $db, $sortColumns = "expertise DESC, name ASC") {
		$result = $db->querySelect("*", $websoccer->getConfig("db_prefix") . "_youthscout", "1=1 ORDER BY " . $sortColumns);
		
		$scouts = array();
		while ($scout = $result->fetch_array()) {
			$scouts[] = $scout;
		}
		$result->free();
		
		return $scouts;
	}
	
	/**
	 * Provides scout data set with specified ID. Throws exception if invalid ID.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection-
	 * @param I18n $i18n messages context.
	 * @param int $scoutId ID of scout
	 * @throws Exception if no scout with specified ID could be found.
	 * @return array assoc array of scout data.
	 */
	public static function getScoutById(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $scoutId) {
		$result = $db->querySelect("*", $websoccer->getConfig("db_prefix") . "_youthscout", "id = %d", $scoutId);
		$scout = $result->fetch_array();
		$result->free();
		
		if (!$scout) {
			throw new Exception($i18n->getMessage("youthteam_scouting_err_invalidscout"));
		}
		
		return $scout;
	}
	
	/**
	 * Provide the timestamp of the last scouting execution by the specified team.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team.
	 * @return number UNIX timestamp of last scouting. 0 if never executed before.
	 */
	public static function getLastScoutingExecutionTime(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$result = $db->querySelect("scouting_last_execution", $websoccer->getConfig("db_prefix") . "_verein", 
				"id = %d", $teamId);
		$scouted = $result->fetch_array();
		$result->free();
		
		if (!$scouted) {
			return 0;
		}
		
		return $scouted["scouting_last_execution"];
	}
	
	/**
	 * Provides a list of country names which are feasable for player generation (i.e. scouting).
	 * These are the names of the folders containing dummy names.
	 * 
	 * @return array array of (untranslated) country names which can be used for generating new players.
	 */
	public static function getPossibleScoutingCountries() {
		$iterator = new DirectoryIterator(BASE_FOLDER . "/admin/config/names/");
		
		$countries = array();
		while($iterator->valid()) {
			if ($iterator->isDir() && !$iterator->isDot()) {
				$countries[] = $iterator->getFilename();
			}
			
			$iterator->next();
		}
		
		return $countries;
	}
	
	/**
	 * Provides number of open youth match requests.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @return int total number of open requests.
	 */
	public static function countMatchRequests(WebSoccer $websoccer, DbConnection $db) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthmatch_request";
	
	
		$result = $db->querySelect("COUNT(*) AS hits", $fromTable, "1=1");
		$requests = $result->fetch_array();
		$result->free();
	
		if ($requests) {
			return $requests["hits"];
		}
	
		return 0;
	}
	
	/**
	 * Provides open match requests.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $startIndex Fetch start index.
	 * @param int $entries_per_page Number of items to fetch.
	 * @return array list of found requests incl. team and user summary.
	 */
	public static function getMatchRequests(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
	
		$columns = array(
				"R.id" => "request_id",
				"R.matchdate" => "matchdate",
				"R.reward" => "reward",
				"C.name" => "team_name",
				"C.id" => "team_id",
				"U.id" => "user_id",
				"U.nick" => "user_nick",
				"U.email" => "user_email",
				"U.picture" => "user_picture"
		);
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthmatch_request AS R";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS C ON C.id = R.team_id";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_user AS U ON U.id = C.user_id";
		
		$whereCondition = "1=1 ORDER BY R.matchdate ASC";
	
		$requests = array();
	
		$limit = $startIndex .",". $entries_per_page;
		$result = $db->querySelect($columns, $fromTable, $whereCondition, null, $limit);
		while ($request = $result->fetch_array()) {
			$request["user_picture"] = UsersDataService::getUserProfilePicture($websoccer, $request["user_picture"], $request["user_email"]);
			$requests[] = $request;
		}
		$result->free();
	
		return $requests;
	}
	
	/**
	 * Removes open match requests which cannot be approved any more because match would start to later otherwise.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 */
	public static function deleteInvalidOpenMatchRequests(WebSoccer $websoccer, DbConnection $db) {
		$timeBoundary = $websoccer->getNowAsTimestamp() + $websoccer->getConfig("youth_matchrequest_accept_hours_in_advance");
		$db->queryDelete($websoccer->getConfig("db_prefix") . "_youthmatch_request", "matchdate <= %d", $timeBoundary);
	}
}
?>