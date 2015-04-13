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
 * Hire lendable player.
 */
class BorrowPlayerController implements IActionController {
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
		
		// check if user has team
		if ($clubId == null) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		// check if it is already own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $parameters["id"]);
		if ($clubId == $player["team_id"]) {
			throw new Exception($this->_i18n->getMessage("lending_hire_err_ownplayer"));
		}
		
		// check if player is borrowed by any user
		if ($player["lending_owner_id"] > 0) {
			throw new Exception($this->_i18n->getMessage("lending_hire_err_borrowed_player"));
		}
		
		// check if player is offered for lending
		if ($player["lending_fee"] == 0) {
			throw new Exception($this->_i18n->getMessage("lending_hire_err_notoffered"));
		}
		
		// check if player is on transfermarket
		if ($player["player_transfermarket"] > 0) {
			throw new Exception($this->_i18n->getMessage("lending_err_on_transfermarket"));
		}
		
		// check min and max duration
		if ($parameters["matches"] < $this->_websoccer->getConfig("lending_matches_min")
				|| $parameters["matches"] > $this->_websoccer->getConfig("lending_matches_max")) {
			throw new Exception(sprintf($this->_i18n->getMessage("lending_hire_err_illegalduration"), 
					$this->_websoccer->getConfig("lending_matches_min"), $this->_websoccer->getConfig("lending_matches_max")));
		}
			
		// check player's contract length
		if ($parameters["matches"] >= $player["player_contract_matches"]) {
			throw new Exception($this->_i18n->getMessage("lending_hire_err_contractendingtoosoon", $player["player_contract_matches"]));
		}
		
		// check if team can pay fee and salary
		$fee = $parameters["matches"] * $player["lending_fee"];
		// team should have the money for at least 5 matches to pay him
		$minBudget = $fee + 5 * $player["player_contract_salary"];
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		if ($team["team_budget"] < $minBudget) {
			throw new Exception($this->_i18n->getMessage("lending_hire_err_budget_too_low"));
		}
		
		// deduct and credit fee
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $clubId, $fee, "lending_fee_subject", $player["team_name"]);
		BankAccountDataService::creditAmount($this->_websoccer, $this->_db, $player["team_id"], $fee, "lending_fee_subject", $team["team_name"]);
		
		$this->updatePlayer($player["player_id"], $player["team_id"], $clubId, $parameters["matches"]);
		
		// create notification for owner
		$playerName = (strlen($player["player_pseudonym"])) ? $player["player_pseudonym"] : $player["player_firstname"] . " " . $player["player_lastname"];
		if ($player["team_user_id"]) {
			NotificationsDataService::createNotification($this->_websoccer, $this->_db, $player["team_user_id"], "lending_notification_lent",
				array("player" => $playerName, "matches" => $parameters["matches"], "newteam" => $team["team_name"]), 
				"lending_lent", "player", "id=" . $player["player_id"]);
		}
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("lending_hire_success"),
				""));
		
		return "myteam";
	}
	
	private function updatePlayer($playerId, $ownerId, $clubId, $matches) {
		
		$columns = array("lending_matches" => $matches, "lending_owner_id" => $ownerId, "verein_id" => $clubId);
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "id = %d";
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $playerId);
	}
	
}

?>