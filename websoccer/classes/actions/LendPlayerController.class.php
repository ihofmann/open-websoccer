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
 * Marks player as ready for borrowing.
 */
class LendPlayerController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		// check if feature is enabled
		if (!$this->_websoccer->getConfig("lending_enabled")) {
			return NULL;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if it is own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $parameters["id"]);
		if ($clubId != $player["team_id"]) {
			throw new Exception($this->_i18n->getMessage("lending_err_notownplayer"));
		}
		
		// check if player is borrowed by user
		if ($player["lending_owner_id"] > 0) {
			throw new Exception($this->_i18n->getMessage("lending_err_borrowed_player"));
		}
		
		// check if player is already offered for lending
		if ($player["lending_fee"] > 0) {
			throw new Exception($this->_i18n->getMessage("lending_err_alreadyoffered"));
		}
		
		// check if player is on transfermarket
		if ($player["player_transfermarket"] > 0) {
			throw new Exception($this->_i18n->getMessage("lending_err_on_transfermarket"));
		}
		
		// check violation of minimum team size
		$teamSize = TeamsDataService::getTeamSize($this->_websoccer, $this->_db, $clubId);
		if ($teamSize <= $this->_websoccer->getConfig("transfermarket_min_teamsize")) {
			throw new Exception($this->_i18n->getMessage("lending_err_teamsize_too_small", $teamSize));
		}
		
		// remaining contract is too short
		$minBidBoundary = round($player["player_marketvalue"] / 2);
		if ($player["player_contract_matches"] <= $this->_websoccer->getConfig("lending_matches_min")) {
			throw new Exception($this->_i18n->getMessage("lending_err_contract_too_short"));
		}
		
		$this->updatePlayer($player["player_id"], $parameters["fee"]);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("lend_player_success"),
				""));
		
		return "myteam";
	}
	
	private function updatePlayer($playerId, $fee) {
		
		$columns = array("lending_fee" => $fee);
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "id = %d";
		$parameters = $playerId;
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
}

?>