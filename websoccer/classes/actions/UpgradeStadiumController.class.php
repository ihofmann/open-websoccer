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
 * Upgrades a stadium maintenance item.
 */
class UpgradeStadiumController implements IActionController {
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
			return null;
		}
		
		// validate type parameter
		$type = $parameters["type"];
		if (!in_array($type, array("pitch", "videowall", "seatsquality", "vipquality"))) {
			throw new Exception("illegal parameter: type");
		}
		
		$stadium = StadiumsDataService::getStadiumByTeamId($this->_websoccer, $this->_db, $teamId);
		if (!$stadium) {
			return null;
		}
		
		$existingLevel = $stadium["level_" . $type];
		
		// upgradable?
		if ($existingLevel >= 5) {
			throw new Exception($this->_i18n->getMessage("stadium_upgrade_err_not_upgradable"));
		}
		
		// can user afford it?
		$costs = StadiumsDataService::computeUpgradeCosts($this->_websoccer, $type, $stadium);
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $teamId);
		
		if ($team["team_budget"] <= $costs) {
			throw new Exception($this->_i18n->getMessage("stadium_extend_err_too_expensive"));
		}
		
		// debit money
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $teamId,
			$costs,
			"stadium_upgrade_transaction_subject",
			$stadium["name"]);
		
		// update stadium
		$maintenanceDue = (int) $this->_websoccer->getConfig("stadium_maintenanceinterval_" . $type);
		$this->_db->queryUpdate(array(
				"level_" . $type => $existingLevel + 1,
				"maintenance_" . $type => $maintenanceDue
				), $this->_websoccer->getConfig("db_prefix") . "_stadion", "id = %d", $stadium["stadium_id"]);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("stadium_upgrade_success"),
				$this->_i18n->getMessage("stadium_upgrade_success_details")));
		
		return "stadium";
	}
	
}

?>