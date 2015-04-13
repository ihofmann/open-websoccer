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
 * Hires a selected trainer.
 */
class ChooseTrainerController implements IActionController {
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
		
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		if (TrainingDataService::countRemainingTrainingUnits($this->_websoccer, $this->_db, $teamId)) {
			throw new Exception($this->_i18n->getMessage("training_choose_trainer_err_existing_units"));
		}	
		
		// trainer info
		$trainer = TrainingDataService::getTrainerById($this->_websoccer, $this->_db, $parameters["id"]);
		if (!isset($trainer["id"])) {
			throw new Exception("invalid ID");
		}
		
		// can team afford it?
		$numberOfUnits = (int) $parameters["units"];
		
		$totalCosts = $numberOfUnits * $trainer["salary"];
		
		$teamInfo = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $teamId);
		if ($teamInfo["team_budget"] <= $totalCosts) {
			throw new Exception($this->_i18n->getMessage("training_choose_trainer_err_too_expensive"));
		}
		
		// try to debit premium fee
		if ($trainer['premiumfee']) {
			PremiumDataService::debitAmount($this->_websoccer, $this->_db, $user->id, $trainer['premiumfee'], "choose-trainer");
		}
		
		// debit money
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $teamId,
			$totalCosts,
			"training_trainer_salary_subject",
			$trainer["name"]);
		
		// create new units
		$columns["team_id"] = $teamId;
		$columns["trainer_id"] = $trainer["id"];
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_training_unit";
		
		for ($unitNo = 1; $unitNo <= $numberOfUnits; $unitNo++) {
			$this->_db->queryInsert($columns, $fromTable);
		}
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("saved_message_title"),
				""));
		
		// redirect to training overview
		return "training";
	}
	
}

?>