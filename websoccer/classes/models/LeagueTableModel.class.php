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
 * Providing data for the league tables view.
 * 
 * @author Ingo Hofmann
 */
class LeagueTableModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_leagueId;
	private $_seasonId;
	private $_type;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		
		$this->_leagueId = (int) $this->_websoccer->getRequestParameter("id");
		$this->_seasonId = $this->_websoccer->getRequestParameter("seasonid");
		$this->_type = $this->_websoccer->getRequestParameter("type");
		
		// pre-select user's league in case no other league selected
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		if ($this->_leagueId == 0 && $clubId > 0) {
			$result = $db->querySelect("liga_id", $this->_websoccer->getConfig("db_prefix") . "_verein", 
					"id = %d", $clubId, 1);
			$club = $result->fetch_array();
			$result->free();
			
			$this->_leagueId = $club["liga_id"];
		}
	}
	
	public function renderView() {
		// do not render if no proper league ID has been provided
		return ($this->_leagueId  > 0);
	}
	
	public function getTemplateParameters() {
		$markers = array();
		$teams = array();
		
		// get data for current standing
		if ($this->_seasonId == null && $this->_type == null) {
			$teams = TeamsDataService::getTeamsOfLeagueOrderedByTableCriteria($this->_websoccer, $this->_db, $this->_leagueId);
			
			// get table markers
			$fromTable = $this->_websoccer->getConfig("db_prefix") ."_tabelle_markierung";
			
			$columns["bezeichnung"] = "name";
			$columns["farbe"] = "color";
			$columns["platz_von"] = "place_from";
			$columns["platz_bis"] = "place_to";
			
			$whereCondition = "liga_id = %d ORDER BY place_from ASC";
			
			$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $this->_leagueId);
			while ($marker = $result->fetch_array()) {
				$markers[] = $marker;
			}
			$result->free();
			
			// get data of specified season or home-/away table
		} else {
			
			$seasonId = 0;
			
			// no season selected, so select current one
			if ($this->_seasonId == null) {
				$result = $this->_db->querySelect("id", $this->_websoccer->getConfig("db_prefix") ."_saison", 
				"liga_id = %d AND beendet = '0' ORDER BY name DESC", $this->_leagueId, 1);
				$season = $result->fetch_array();
				$result->free();
				
				if ($season["id"]) {
					$seasonId = $season["id"];
				}
			} else {
				$seasonId = $this->_seasonId;
			}
			
			if ($seasonId) {
				$teams = TeamsDataService::getTeamsOfSeasonOrderedByTableCriteria($this->_websoccer, $this->_db, $seasonId, $this->_type);
			}
		}
		
		// get completed seasons
		$seasons = array();
		$result = $this->_db->querySelect("id,name", $this->_websoccer->getConfig("db_prefix") ."_saison", 
				"liga_id = %d AND beendet = '1' ORDER BY name DESC", $this->_leagueId);
		while ($season = $result->fetch_array()) {
			$seasons[] = $season;
		}
		$result->free();
		
		return array("leagueId" => $this->_leagueId, "teams" => $teams, "markers" => $markers, "seasons" => $seasons);
	}
	
	
}

?>