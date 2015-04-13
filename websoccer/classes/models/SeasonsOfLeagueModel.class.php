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
 * @author Ingo Hofmann
 */
class SeasonsOfLeagueModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_leagueId;
	
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
		$this->_leagueId = (int) $this->_websoccer->getRequestParameter("leagueid");
		return ($this->_leagueId > 0);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		// get seasons
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_saison AS S";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") ."_liga AS L ON L.id = S.liga_id";
		$whereCondition = "S.liga_id = %d ORDER BY beendet DESC, name ASC";
		
		$seasons = array();
		
		$result = $this->_db->querySelect("S.id AS id, S.name AS name, L.name AS league_name", $fromTable, $whereCondition, $this->_leagueId);
		while ($season = $result->fetch_array()) {
			$seasons[] = $season;
		}
		$result->free();
		
		$league_name = "";
		if (isset($seasons[0]["league_name"])) {
			$league_name = $seasons[0]["league_name"];
		}
		
		// current / max matchday
		$currentMatchDay = 0;
		$maxMatchDay = 0;
		
		if ($this->_websoccer->getRequestParameter("seasonid") != null) {
			$seasonId = $this->_websoccer->getRequestParameter("seasonid");
			$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spiel";
			$condition = "saison_id = %d";
			
			$result = $this->_db->querySelect("MAX(spieltag) AS maxMatchday", $fromTable, $condition, $seasonId);
			$match = $result->fetch_array();
			$result->free();
			$maxMatchDay = $match["maxMatchday"];
			
			$result = $this->_db->querySelect("MAX(spieltag) AS currentMatchday", $fromTable, $condition . " AND berechnet = '1'", $seasonId);
			$match = $result->fetch_array();
			$result->free();
			$currentMatchDay = $match["currentMatchday"];
		}
		
	
		return array("seasons" => $seasons, "league_name" => $league_name, "currentMatchDay" => $currentMatchDay, "maxMatchDay" => $maxMatchDay);
	}
	
	
}

?>