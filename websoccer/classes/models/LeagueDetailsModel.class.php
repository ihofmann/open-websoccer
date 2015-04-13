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
class LeagueDetailsModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return TRUE;
	}
	
	public function getTemplateParameters() {
		
		$league = null;
		
		$leagueId = (int) $this->_websoccer->getRequestParameter("id");
		
		// pre-select user's league
		if ($leagueId == 0) {
			$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
			if ($clubId > 0) {
				$result = $this->_db->querySelect("liga_id", $this->_websoccer->getConfig("db_prefix") . "_verein",
						"id = %d", $clubId, 1);
				$club = $result->fetch_array();
				$result->free();
					
				$leagueId = $club["liga_id"];
			}
		}
		
		if ($leagueId > 0) {
			$league = LeagueDataService::getLeagueById($this->_websoccer, $this->_db, $leagueId);
			
			if (!isset($league["league_id"])) {
				throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
			}
		}

		
		return array("league" => $league, "leagues" => LeagueDataService::getLeaguesSortedByCountry($this->_websoccer, $this->_db));
	}
	
	
}

?>