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
class CupGroupDetailsModel implements IModel {
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
		
		$cupRoundId = $this->_websoccer->getRequestParameter("roundid");
		$cupGroup = $this->_websoccer->getRequestParameter("group");
		
		$columns = "C.name AS cup_name, R.name AS round_name";
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_cup_round AS R";
		$fromTable .= " INNER JOIN " . $this->_websoccer->getConfig("db_prefix") . "_cup AS C ON C.id = R.cup_id";
		
		$result = $this->_db->querySelect($columns, $fromTable, "R.id = %d", $cupRoundId);
		$round = $result->fetch_array();
		$result->free();
		
		$matches = MatchesDataService::getMatchesByCupRoundAndGroup($this->_websoccer, $this->_db, $round["cup_name"], $round["round_name"], $cupGroup);
		
		return array("matches" => $matches, "groupteams" => CupsDataService::getTeamsOfCupGroupInRankingOrder($this->_websoccer, 
			$this->_db, $cupRoundId, $cupGroup));
	}
	
	
}

?>