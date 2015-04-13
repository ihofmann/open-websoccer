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
 * Provides all information about a requested youth match.
 */
class YouthMatchReportModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return $this->_websoccer->getConfig("youth_enabled");
	}
	
	public function getTemplateParameters() {
		
		$match = YouthMatchesDataService::getYouthMatchinfoById($this->_websoccer, $this->_db, $this->_i18n, 
				$this->_websoccer->getRequestParameter("id"));
		
		// get players and their statistics
		$players = array();
		$statistics = array();
		
		$result = $this->_db->querySelect("*", $this->_websoccer->getConfig("db_prefix") . "_youthmatch_player", 
				"match_id = %d AND minutes_played > 0 ORDER BY playernumber ASC", $match["id"]);
		while ($playerinfo = $result->fetch_array()) {
			
			if ($playerinfo["team_id"] == $match["home_team_id"]) {
				$teamPrefix = "home";
			} else {
				$teamPrefix = "guest";
			}
			
			// init array
			if (!isset($statistics[$teamPrefix])) {
				$statistics[$teamPrefix]["avg_strength"] = 0;
				$statistics[$teamPrefix]["ballcontacts"] = 0;
				$statistics[$teamPrefix]["wontackles"] = 0;
				$statistics[$teamPrefix]["shoots"] = 0;
				$statistics[$teamPrefix]["passes_successed"] = 0;
				$statistics[$teamPrefix]["passes_failed"] = 0;
				$statistics[$teamPrefix]["assists"] = 0;
			}
			
			$players[$teamPrefix][] = $playerinfo;
			
			$statistics[$teamPrefix]["avg_strength"] = $statistics[$teamPrefix]["avg_strength"] + $playerinfo["strength"];
			$statistics[$teamPrefix]["ballcontacts"] = $statistics[$teamPrefix]["ballcontacts"] + $playerinfo["ballcontacts"];
			$statistics[$teamPrefix]["wontackles"] = $statistics[$teamPrefix]["wontackles"] + $playerinfo["wontackles"];
			$statistics[$teamPrefix]["shoots"] = $statistics[$teamPrefix]["shoots"] + $playerinfo["shoots"];
			$statistics[$teamPrefix]["passes_successed"] = $statistics[$teamPrefix]["passes_successed"] + $playerinfo["passes_successed"];
			$statistics[$teamPrefix]["passes_failed"] = $statistics[$teamPrefix]["passes_failed"] + $playerinfo["passes_failed"];
			$statistics[$teamPrefix]["assists"] = $statistics[$teamPrefix]["assists"] + $playerinfo["assists"];
			
		}
		$result->free();
		
		// computed statistics
		if (isset($statistics["guest"]["wontackles"]) && isset($statistics["home"]["wontackles"])) {
			$statistics["home"]["losttackles"] = $statistics["guest"]["wontackles"];
			$statistics["guest"]["losttackles"] = $statistics["home"]["wontackles"];
		}
		
		if (isset($statistics["guest"]["avg_strength"]) && isset($statistics["home"]["avg_strength"])) {
			$statistics["home"]["avg_strength"] = round($statistics["home"]["avg_strength"] / count($players["home"]));
			$statistics["guest"]["avg_strength"] = round($statistics["guest"]["avg_strength"] / count($players["guest"]));
		}
		
		if (isset($statistics["guest"]["ballcontacts"]) && isset($statistics["home"]["ballcontacts"])) {
			$statistics["home"]["ballpossession"] = round($statistics["home"]["ballcontacts"] * 100 / ($statistics["home"]["ballcontacts"] + $statistics["guest"]["ballcontacts"]));
			$statistics["guest"]["ballpossession"] = round($statistics["guest"]["ballcontacts"] * 100 / ($statistics["home"]["ballcontacts"] + $statistics["guest"]["ballcontacts"]));
		}
		
		$reportMessages = YouthMatchesDataService::getMatchReportItems($this->_websoccer, $this->_db, $this->_i18n, $match["id"]);
		
		return array("match" => $match, "players" => $players, 
				"statistics" => $statistics, "reportMessages" => $reportMessages);
	}
	
}

?>