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
class MatchStatisticsModel implements IModel {
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
		
		$matchId = (int) $this->_websoccer->getRequestParameter("id");
		if ($matchId < 1) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$match = MatchesDataService::getMatchById($this->_websoccer, $this->_db, $matchId);
		
		// get statistics
		$columns["SUM(shoots)"] = "shoots";
		$columns["SUM(ballcontacts)"] = "ballcontacts";
		$columns["SUM(wontackles)"] = "wontackles";
		$columns["SUM(passes_successed)"] = "passes_successed";
		$columns["SUM(passes_failed)"] = "passes_failed";
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_spiel_berechnung";
		$whereCondition = "spiel_id = %d AND team_id = %d";
		
		// home team
		$parameters = array($matchId, $match["match_home_id"]);
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$homeStatistics = $result->fetch_array();
		$result->free();
		
		// guest team
		$parameters = array($matchId, $match["match_guest_id"]);
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$guestStatistics = $result->fetch_array();
		$result->free();
		
		return array("match" => $match, "homeStatistics" => $homeStatistics, "guestStatistics" => $guestStatistics);
	}
	
}

?>