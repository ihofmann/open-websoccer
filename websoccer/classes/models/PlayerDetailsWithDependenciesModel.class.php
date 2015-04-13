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
class PlayerDetailsWithDependenciesModel implements IModel {
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
		
		$playerId = (int) $this->_websoccer->getRequestParameter("id");
		if ($playerId < 1) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $playerId);
		
		if (!isset($player["player_id"])) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$grades = $this->_getGrades($playerId);
		
		$transfers = TransfermarketDataService::getCompletedTransfersOfPlayer($this->_websoccer, $this->_db, $playerId);
		return array("player" => $player, "grades" => $grades, "completedtransfers" => $transfers);
	}
	
	private function _getGrades($playerId) {
		$grades = array();
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spiel_berechnung";
		
		$columns = "note AS grade";
		
		$whereCondition = "spieler_id = %d AND minuten_gespielt > 0 ORDER BY id DESC";
		$parameters = $playerId;
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters, 10);
		while ($grade = $result->fetch_array()) {
			$grades[] = $grade["grade"];
		}		
		
		$grades = array_reverse($grades);
		
		return $grades;
	}
	
}

?>