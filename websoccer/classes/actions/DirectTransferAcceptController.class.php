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
 * Accepts a direct tranfer offer, if valid.
 * 
 * @author Ingo Hofmann
 */
class DirectTransferAcceptController implements IActionController {
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
		if (!$this->_websoccer->getConfig("transferoffers_enabled")) {
			return;
		}
		
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		
		// get offer information
		$result = $this->_db->querySelect("*", $this->_websoccer->getConfig("db_prefix") . "_transfer_offer", "id = %d AND rejected_date = 0 AND admin_approval_pending = '0'",
				$parameters["id"]);
		$offer = $result->fetch_array();
		$result->free();
		
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $offer["player_id"]);
		
		// check if player is already on transfer market
		if ($player["player_transfermarket"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_unsellable"));
		}
		
		// check exchange players
		if ($offer["offer_player1"] || $offer["offer_player2"]) {
			$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
			if ($offer["offer_player1"]) {
				$this->checkExchangePlayer($offer["offer_player1"], $offer["sender_user_id"], $team["team_budget"]);
			}
			
			if ($offer["offer_player2"]) {
				$this->checkExchangePlayer($offer["offer_player2"], $offer["sender_user_id"], $team["team_budget"]);
			}
		}
		
		// check if team is above minimum number of players.
		$teamSize = TeamsDataService::getTeamSize($this->_websoccer, $this->_db, $clubId);
		if ($teamSize < $this->_websoccer->getConfig("transfermarket_min_teamsize")) {
			throw new Exception($this->_i18n->getMessage("sell_player_teamsize_too_small", $teamSize));
		}
		
		// mark offer as pending
		if ($this->_websoccer->getConfig("transferoffers_adminapproval_required")) {
			$this->_db->queryUpdate(array("admin_approval_pending" => "1"), $this->_websoccer->getConfig("db_prefix") . "_transfer_offer", 
					"id = %d", $parameters["id"]);
			
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
					$this->_i18n->getMessage("transferoffer_accepted_title"),
					$this->_i18n->getMessage("transferoffer_accepted_message_approvalpending")));
			
			// execute transfer
		} else {
			DirectTransfersDataService::executeTransferFromOffer($this->_websoccer, $this->_db, $parameters["id"]);
			
			// show success message
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
					$this->_i18n->getMessage("transferoffer_accepted_title"),
					$this->_i18n->getMessage("transferoffer_accepted_message")));
		}
		
		return null;
	}
	
	private function checkExchangePlayer($playerId, $userId, $teamBudget) {
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $playerId);
		$playerName = (strlen($player["player_pseudonym"])) ? $player["player_pseudonym"] : $player["player_firstname"] . " " . $player["player_lastname"];
		
		// Check if player is moved to tranfermarket
		if ($player["player_transfermarket"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_accept_err_exchangeplayer_on_transfermarket", $playerName));
		}
		
		// Check if player is still playing for the user (might have changed)
		if ($player["team_user_id"] != $userId) {
			throw new Exception($this->_i18n->getMessage("transferoffer_accept_err_exchangeplayer_notinteam", $playerName));
		}
		
		// check if user can afford player
		$minBudget = 40 * $player["player_contract_salary"];
		if ($teamBudget < $minBudget) {
			throw new Exception($this->_i18n->getMessage("transferoffer_accept_err_exchangeplayer_salarytoohigh", $playerName));
		}
	}
	
}

?>