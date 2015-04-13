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
 * Data service for youth matches data management.
 */
class YouthMatchesDataService {
	
	/**
	 * Provides information about specified match, incl. all DB table columns plus team name.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n messages context.
	 * @param int $matchId ID of match
	 * @throws Exception if match could not be found. Message is "page not found".
	 * @return array assoc. array of match info.
	 */
	public static function getYouthMatchinfoById(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $matchId) {
		$columns = "M.*, HOME.name AS home_team_name, GUEST.name AS guest_team_name";
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthmatch AS M";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS HOME ON HOME.id = M.home_team_id";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS GUEST ON GUEST.id = M.guest_team_id";
		
		$result = $db->querySelect($columns, $fromTable, "M.id = %d", $matchId);
		$match = $result->fetch_array();
		$result->free();
		
		if (!$match) {
			throw new Exception($i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		return $match;
	}
	
	/**
	 * Provides the number of created matches which involve the specified team and which take place on the same day
	 * as the specified timestamp.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team.
	 * @param int $timestamp UNIX timestamp.
	 * @return int number of actual matches on the same day of the specified timestamp.
	 */
	public static function countMatchesOfTeamOnSameDay(WebSoccer $websoccer, DbConnection $db, $teamId, $timestamp) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthmatch";
	
		$dateObj = new DateTime();
		$dateObj->setTimestamp($timestamp);
		
		$dateObj->setTime(0, 0, 0);
		$minTimeBoundary = $dateObj->getTimestamp();
		
		$dateObj->setTime(23, 59, 59);
		$maxTimeBoundary = $dateObj->getTimestamp();
	
		$result = $db->querySelect("COUNT(*) AS hits", $fromTable, 
				"(home_team_id = %d OR guest_team_id = %d) AND matchdate BETWEEN %d AND %d",
				array($teamId, $teamId, $minTimeBoundary, $maxTimeBoundary));
		$rows = $result->fetch_array();
		$result->free();
	
		if ($rows) {
			return $rows["hits"];
		}
	
		return 0;
	}
	
	/**
	 * Provides number of matches in which the specified team is involved (as home or guest team).
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team-
	 * @return int number of matches.
	 */
	public static function countMatchesOfTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_youthmatch";
	
		$result = $db->querySelect("COUNT(*) AS hits", $fromTable,
				"(home_team_id = %d OR guest_team_id = %d)",
				array($teamId, $teamId));
		$rows = $result->fetch_array();
		$result->free();
	
		if ($rows) {
			return $rows["hits"];
		}
	
		return 0;
	}
	
	/**
	 * Provides list of matches of team (including pictures and user info), ordered by date descending.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $teamId ID of team.
	 * @param int $startIndex start fetch index.
	 * @param int $entries_per_page number of items to fetch
	 * @return array list of matches or empty array if no matches found.
	 */
	public static function getMatchesOfTeam(WebSoccer $websoccer, DbConnection $db, $teamId, $startIndex, $entries_per_page) {
		$tablePrefix = $websoccer->getConfig("db_prefix");
		
		$fromTable = $tablePrefix . "_youthmatch AS M";
		$fromTable .= " INNER JOIN " . $tablePrefix . "_verein AS HOME ON M.home_team_id = HOME.id";
		$fromTable .= " INNER JOIN " . $tablePrefix . "_verein AS GUEST ON M.guest_team_id = GUEST.id";
		$fromTable .= " LEFT JOIN " . $tablePrefix . "_user AS HOMEUSER ON HOME.user_id = HOMEUSER.id";
		$fromTable .= " LEFT JOIN " . $tablePrefix . "_user AS GUESTUSER ON GUEST.user_id = GUESTUSER.id";
		
		// select
		$columns["M.id"] = "match_id";
		$columns["HOME.name"] = "home_team";
		$columns["HOME.bild"] = "home_team_picture";
		$columns["HOME.id"] = "home_id";
		$columns["HOMEUSER.id"] = "home_user_id";
		$columns["HOMEUSER.nick"] = "home_user_nick";
		$columns["HOMEUSER.email"] = "home_user_email";
		$columns["HOMEUSER.picture"] = "home_user_picture";
		$columns["GUEST.name"] = "guest_team";
		$columns["GUEST.bild"] = "guest_team_picture";
		$columns["GUEST.id"] = "guest_id";
		$columns["GUESTUSER.id"] = "guest_user_id";
		$columns["GUESTUSER.nick"] = "guest_user_nick";
		$columns["GUESTUSER.email"] = "guest_user_email";
		$columns["GUESTUSER.picture"] = "guest_user_picture";
		$columns["M.home_goals"] = "home_goals";
		$columns["M.guest_goals"] = "guest_goals";
		$columns["M.simulated"] = "simulated";
		$columns["M.matchdate"] = "date";
		
		$matches = array();
		$limit = $startIndex . "," . $entries_per_page;
		$result = $db->querySelect($columns, $fromTable, "(home_team_id = %d OR guest_team_id = %d) ORDER BY M.matchdate DESC",
				array($teamId, $teamId), $limit);
		while ($matchinfo = $result->fetch_array()) {
			$matchinfo["home_user_picture"] = UsersDataService::getUserProfilePicture($websoccer, $matchinfo["home_user_picture"], $matchinfo["home_user_email"]);
			$matchinfo["guest_user_picture"] = UsersDataService::getUserProfilePicture($websoccer, $matchinfo["guest_user_picture"], $matchinfo["guest_user_email"]);
			$matches[] = $matchinfo;
		}
		$result->free();
		return $matches;
	}
	
	/**
	 * Creates a new item for a youth match report.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $matchId ID of match.
	 * @param int $minute minute at which event happened.
	 * @param string $messageKey Messages key.
	 * @param string $messageData Values for placeholders within message.
	 * @param boolean $isHomeTeamWithBall TRUE if Home team is the main affected team at this event.
	 */
	public static function createMatchReportItem(WebSoccer $websoccer, DbConnection $db, 
			$matchId, $minute, $messageKey, $messageData = null, $isHomeTeamWithBall = FALSE) {
		
		$messageDataStr = "";
		if (is_array($messageData)) {
			$messageDataStr = json_encode($messageData);
		}
		
		$columns = array(
				"match_id" => $matchId,
				"minute" => $minute,
				"message_key" => $messageKey,
				"message_data" => $messageDataStr,
				"home_on_ball" => ($isHomeTeamWithBall) ? "1" : "0"
				);
		$db->queryInsert($columns, $websoccer->getConfig("db_prefix") . "_youthmatch_reportitem");
	}
	
	/**
	 * Provides match report items in current language and with replaced placeholders.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param int $matchId ID of match.
	 * @return array list of match report items with translated messages.
	 */
	public static function getMatchReportItems(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $matchId) {
		// query
		$result = $db->querySelect("*", $websoccer->getConfig("db_prefix") . "_youthmatch_reportitem", 
				"match_id = %d ORDER BY minute ASC", $matchId);
		
		// create formatted items
		$items = array();
		while ($item = $result->fetch_array()) {
			
			$message = $i18n->getMessage($item["message_key"]);
			
			// replace place holders
			if (strlen($item["message_data"])) {
				$messageData = json_decode($item["message_data"], true);
			
				if ($messageData) {
					foreach ($messageData as $placeholderName => $placeholderValue) {
						$message = str_replace("{" . $placeholderName . "}",
								htmlspecialchars($placeholderValue, ENT_COMPAT, "UTF-8"), $message);
					}
				}
			}
			
			// create mapped item
			$items[] = array(
					"minute" => $item["minute"],
					"active_home" => $item["home_on_ball"],
					"message_key" => $item["message_key"],
					"message" => $message
					);
		}
		$result->free();
		
		return $items;
	}
	
}
?>