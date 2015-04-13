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
define("MINIMUM_SATISFACTION_FOR_EXTENSION", 30);
define("SATISFACTION_DECREASE", 10);
define("SATISFACTION_INCREASE", 10);

class ExtendContractController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if it is own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $parameters["id"]);
		if ($clubId != $player["team_id"]) {
			throw new Exception("nice try");
		}
		
		// if player is not happy at club, he does not want to extend at all
		$satisfaction = $player["player_strength_satisfaction"];
		if ($satisfaction < MINIMUM_SATISFACTION_FOR_EXTENSION) {
			throw new Exception($this->_i18n->getMessage("extend-contract_player_is_unhappy"));
		}
		
		// check if player is already on market
		if ($player["player_transfermarket"]) {
			throw new Exception($this->_i18n->getMessage("sell_player_already_on_list"));
		}
		
		// no salary decrease
		if ($parameters["salary"] < $player["player_contract_salary"]) {
			throw new Exception($this->_i18n->getMessage("extend-contract_lower_than_current_salary"));
		}
		
		$averageSalary = $this->getAverageSalary($player["player_strength"]);
		
		// if salary is already higher than average, then just expect 10% more
		if ($player["player_contract_salary"] > $averageSalary) {
			$salaryFactor = 1.10;
		} else {
			// make minimum salary dependent on happiness
			$salaryFactor = (200 - $satisfaction) / 100;
		}
		$salaryFactor = max(1.1, $salaryFactor);
		$minSalary = round($player["player_contract_salary"] * $salaryFactor);
			
		// the salary should be at least 90% of the average, except if this would douple the salary
		if ($averageSalary < ($parameters["salary"] * 2)) {
			$minSalaryOfAverage = round(0.9 * $averageSalary);
			$minSalary = max($minSalary, $minSalaryOfAverage);
		}
		
		if ($parameters["salary"] < $minSalary) {
			// decrease satisfaction
			$this->decreaseSatisfaction($player["player_id"], $player["player_strength_satisfaction"]);
			throw new Exception($this->_i18n->getMessage("extend-contract_salary_too_low"));
		}
		
		// check if club can pay this salary
		TeamsDataService::validateWhetherTeamHasEnoughBudgetForSalaryBid($this->_websoccer, $this->_db, $this->_i18n, $clubId, $parameters["salary"]);
		
		// check goal bonus
		$minGoalBonus = $player["player_contract_goalbonus"] * 1.3;
		if ($parameters["goal_bonus"] < $minGoalBonus) {
			throw new Exception($this->_i18n->getMessage("extend-contract_goalbonus_too_low"));
		}
		
		$this->updatePlayer($player["player_id"], $player["player_strength_satisfaction"], $parameters["salary"], $parameters["goal_bonus"], $parameters["matches"]);
		
		// reset inactivity
		UserInactivityDataService::resetContractExtensionField($this->_websoccer, $this->_db, $user->id);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("extend-contract_success"),
				""));
		
		return null;
	}
	
	private function getAverageSalary($playerStrength) {
		$columns = "AVG(vertrag_gehalt) AS average_salary";
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "w_staerke >= %d AND w_staerke <= %d AND status = 1";
		
		$parameters = array($playerStrength - 10, $playerStrength + 10);
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$avg = $result->fetch_array();
		$result->free();
		
		if (isset($avg["average_salary"])) {
			return $avg["average_salary"];
		}
		
		return $playerStrength;
	}
	
	private function decreaseSatisfaction($playerId, $oldValue) {
		if ($oldValue <= SATISFACTION_DECREASE) {
			return;
		}
		
		$newValue = $oldValue - SATISFACTION_DECREASE;
		$columns["w_zufriedenheit"] = $newValue;
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "id = %d";
		$parameters = $playerId;
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
	public function updatePlayer($playerId, $oldSatisfaction, $newSalary, $newGoalBonus, $newMatches) {
		$satisfaction = min(100, $oldSatisfaction + SATISFACTION_INCREASE);
		
		$columns["w_zufriedenheit"] = $satisfaction;
		$columns["vertrag_gehalt"] = $newSalary;
		$columns["vertrag_torpraemie"] = $newGoalBonus;
		$columns["vertrag_spiele"] = $newMatches;
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "id = %d";
		$parameters = $playerId;
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
}

?>