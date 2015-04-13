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
 * Provides best players of a match day, if all matches have been played.
 */
class TeamOfTheDayModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::renderView()
	 */
	public function renderView() {
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$players = array();
		$positions;
		
		// leagues
		$leagues = LeagueDataService::getLeaguesSortedByCountry($this->_websoccer, $this->_db);
		$leagueId = $this->_websoccer->getRequestParameter("leagueid");
		
		// pre-select user's league in case no other league selected
		if (!$leagueId) {
			$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
			if ($clubId > 0) {
				$result = $this->_db->querySelect("liga_id", $this->_websoccer->getConfig("db_prefix") . "_verein",
						"id = %d", $clubId, 1);
				$club = $result->fetch_array();
				$result->free();
					
				$leagueId = $club["liga_id"];
			}
		}
		
		// seasons
		$seasons = array();
		$seasonId = $this->_websoccer->getRequestParameter("seasonid");
		if ($leagueId) {
			$fromTable = $this->_websoccer->getConfig("db_prefix") ."_saison";
			$whereCondition = "liga_id = %d ORDER BY name ASC";
			$result = $this->_db->querySelect("id, name, beendet", $fromTable, $whereCondition, $leagueId);
			while ($season = $result->fetch_array()) {
				$seasons[] = $season;
				if (!$seasonId && !$season["beendet"]) {
					$seasonId = $season["id"];
				}
			}
			$result->free();
		}
		
		// get available match days
		$matchday = $this->_websoccer->getRequestParameter("matchday");
		$maxMatchDay = 0;
		$openMatchesExist = FALSE;
		if ($seasonId) {
			$result = $this->_db->querySelect("MAX(spieltag) AS max_matchday", 
					$this->_websoccer->getConfig("db_prefix") . "_spiel", "saison_id = %d AND berechnet = '1'", $seasonId);
			$matches = $result->fetch_array();
			$result->free();
			
			if ($matches) {
				$maxMatchDay = $matches["max_matchday"];
				
				if (!$matchday) {
					$matchday = $maxMatchDay;
				}
				
				// check if there are still open matches
				$result = $this->_db->querySelect("COUNT(*) AS hits",
						$this->_websoccer->getConfig("db_prefix") . "_spiel", "saison_id = %d AND spieltag = %d AND berechnet != '1'", 
						array($seasonId, $matchday));
				$openmatches = $result->fetch_array();
				$result->free();
				
				if ($openmatches && $openmatches["hits"]) {
					$openMatchesExist = TRUE;
				} else {
					$this->getTeamOfTheDay($seasonId, $matchday, $players);
				}
			}
		}
		
		return array("leagues" => $leagues, 
				"leagueId" => $leagueId, 
				"seasons" => $seasons, 
				"seasonId" => $seasonId,
				"maxMatchDay" => $maxMatchDay,
				"matchday" => $matchday,
				"openMatchesExist" => $openMatchesExist,
				"players" => $players);
	}
	
	private function getTeamOfTheDay($seasonId, $matchday, &$players) {
		
		// make sure that parameters are actually integers, since we use them directly in the query below, without escaping
		$seasonId = (int) $seasonId;
		
		// get team of the season
		if ($matchday == -1) {
			$this->findPlayersForTeamOfSeason($seasonId, array("T"), 1, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("LV"), 1, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("IV"), 2, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("RV"), 1, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("LM"), 1, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("DM", "ZM", "OM"), 2, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("RM"), 1, $players);
			$this->findPlayersForTeamOfSeason($seasonId, array("LS", "MS", "RS"), 2, $players);
			return;
		}
		
		$columns = array(
				"S.id" => "statistic_id",
				"S.spieler_id" => "player_id",
				"S.name" => "player_name",
				"P.picture" => "picture",
				"S.position" => "position",
				"S.position_main" => "position_main",
				"S.note" => "grade",
				"S.tore" => "goals",
				"S.assists" => "assists",
				"T.name" => "team_name",
				"T.bild" => "team_picture",
				"(SELECT COUNT(*) FROM ". $this->_websoccer->getConfig("db_prefix") . "_teamoftheday AS STAT WHERE STAT.season_id = $seasonId AND STAT.player_id = S.spieler_id)" => "memberoftopteam"
		);
		
		// concrete matchday: get from cache
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_teamoftheday AS C";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_spiel_berechnung AS S ON S.id = C.statistic_id";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_spiel AS M ON M.id = S.spiel_id";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_verein AS T ON T.id = S.team_id";
		$fromTable .= " LEFT JOIN " . $this->_websoccer->getConfig("db_prefix") . "_spieler AS P ON P.id = S.spieler_id";
		$result = $this->_db->querySelect($columns, $fromTable, "C.season_id = %d AND C.matchday = %d", array($seasonId, $matchday));
		while ($player = $result->fetch_array()) {
			$players[] = $player;
		}
		$result->free();
		
		// find from DB
		if (!count($players)) {
			$this->findPlayers($columns, $seasonId, $matchday, array("T"), 1, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("LV"), 1, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("IV"), 2, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("RV"), 1, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("LM"), 1, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("DM", "ZM", "OM"), 2, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("RM"), 1, $players);
			$this->findPlayers($columns, $seasonId, $matchday, array("LS", "MS", "RS"), 2, $players);
		}
	}
	
	private function findPlayers($columns, $seasonId, $matchday, $mainPositions, $limit, &$players) {
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_spiel_berechnung AS S";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_spiel AS M ON M.id = S.spiel_id";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_verein AS T ON T.id = S.team_id";
		$fromTable .= " LEFT JOIN " . $this->_websoccer->getConfig("db_prefix") . "_spieler AS P ON P.id = S.spieler_id";
		
		$whereCondition = "M.saison_id = %d AND M.spieltag = %d AND (S.position_main = '";
		$whereCondition .= implode("' OR S.position_main = '", $mainPositions);
		$whereCondition .= "') ORDER BY S.note ASC, S.tore DESC, S.assists DESC, S.wontackles DESC";
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, array($seasonId, $matchday), $limit);
		while ($player = $result->fetch_array()) {
			$players[] = $player;
			
			// save in cache
			$this->_db->queryInsert(array(
					"season_id" => $seasonId,
					"matchday" => $matchday,
					"position_main" => $player["position_main"],
					"statistic_id" => $player["statistic_id"],
					"player_id" => $player["player_id"]
					), $this->_websoccer->getConfig("db_prefix") . "_teamoftheday");
		}
		$result->free();
	}
	
	private function findPlayersForTeamOfSeason($seasonId, $mainPositions, $limit, &$players) {
		$columns = array(
				"P.id" => "player_id",
				"P.vorname" => "firstname",
				"P.nachname" => "lastname",
				"P.kunstname" => "pseudonym",
				"P.picture" => "picture",
				"P.position" => "position",
				"C.position_main" => "position_main",
				"T.name" => "team_name",
				"T.bild" => "team_picture",
				"(SELECT COUNT(*) FROM ". $this->_websoccer->getConfig("db_prefix") . "_teamoftheday AS STAT WHERE STAT.season_id = $seasonId AND STAT.player_id = P.id)" => "memberoftopteam"
		);
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_teamoftheday AS C";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_spieler AS P ON P.id = C.player_id";
		$fromTable .= " LEFT JOIN " . $this->_websoccer->getConfig("db_prefix") . "_verein AS T ON T.id = P.verein_id";
		
		$whereCondition = "C.season_id = %d AND (C.position_main = '";
		$whereCondition .= implode("' OR C.position_main = '", $mainPositions);
		$whereCondition .= "') ";
		
		// do not consider already found players
		foreach ($players as $foundPlayer) {
			$whereCondition .= " AND  P.id != " . $foundPlayer['player_id'];
		}
		$whereCondition .= " GROUP BY P.id ORDER BY memberoftopteam DESC";
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $seasonId, $limit);
		while ($player = $result->fetch_array()) {
			$player["player_name"] = (strlen($player["pseudonym"])) ? $player["pseudonym"] : $player["firstname"] . " " . $player["lastname"];
			$players[] = $player;
		}
		$result->free();
	}
	
}

?>