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
 * Controller that removes player from team.
 * 
 * @author Ingo Hofmann
 */
class FirePlayerController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		// check if feature is enabled
		if (!$this->_websoccer->getConfig("enable_player_resignation")) {
			return;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if it is own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $parameters["id"]);
		if ($clubId != $player["team_id"]) {
			throw new Exception("nice try");
		}
		
		// check violation of minimum team size
		$teamSize = $this->getTeamSize($clubId);
		if ($teamSize <= $this->_websoccer->getConfig("transfermarket_min_teamsize")) {
			throw new Exception($this->_i18n->getMessage("sell_player_teamsize_too_small", $teamSize));
		}
		
		// check and withdraw compensation
		if ($this->_websoccer->getConfig("player_resignation_compensation_matches") > 0) {
			$compensation = $this->_websoccer->getConfig("player_resignation_compensation_matches") * $player["player_contract_salary"];
			
			$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
			if ($team["team_budget"] <= $compensation) {
				throw new Exception($this->_i18n->getMessage("fireplayer_tooexpensive"));
			}
			
			BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $clubId, $compensation, "fireplayer_compensation_subject", 
				$player["player_firstname"] . " " . $player["player_lastname"]);
			
		}
		
		$this->updatePlayer($player["player_id"]);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("fireplayer_success"),
				""));
		
		return null;
	}
	
	private function updatePlayer($playerId) {
		
		$columns["verein_id"] = "";
		$columns["vertrag_spiele"] = 0;
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "id = %d";
		$parameters = $playerId;
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
	private function getTeamSize($clubId) {
		$columns = "COUNT(*) AS number";
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "verein_id = %d AND status = 1 AND transfermarkt != 1";
		$parameters = $clubId;
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$players = $result->fetch_array();
		$result->free();
		
		return $players["number"];
	}
	
}

?>