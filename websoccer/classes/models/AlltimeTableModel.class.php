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
 * Providing data for the all-time league table view.
 * 
 * @author Ingo Hofmann
 */
class AlltimeTableModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_leagueId;
	private $_type;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		
		$this->_leagueId = (int) $this->_websoccer->getRequestParameter("id");
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
		$teams = TeamsDataService::getTeamsOfLeagueOrderedByAlltimeTableCriteria($this->_websoccer, $this->_db, $this->_leagueId, $this->_type);
		
		return array("leagueId" => $this->_leagueId, "teams" => $teams);
	}
	
}

?>