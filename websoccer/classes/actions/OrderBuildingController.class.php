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
 * Validates and stores a building order for the stadium environment.
 */
class OrderBuildingController implements IActionController {
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
		
		$buildingId = $parameters['id'];
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		if (!$teamId) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		$dbPrefix = $this->_websoccer->getConfig('db_prefix');
		$result = $this->_db->querySelect('*', $dbPrefix . '_stadiumbuilding', 'id = %d', $buildingId);
		$building = $result->fetch_array();
		$result->free();
		
		if (!$building) {
			// no i18n required since this should actually not happen if used properly.
			throw new Exception('illegal building.');
		}
		
		// check budget
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $teamId);
		if ($team['team_budget'] <= $building['costs']) {
			throw new Exception($this->_i18n->getMessage('stadiumenvironment_build_err_too_expensive'));
		}
		
		// check if already exists in team
		$result = $this->_db->querySelect('*', $dbPrefix . '_buildings_of_team', 'team_id = %d AND building_id = %d', array($teamId, $buildingId));
		$buildingExists = $result->fetch_array();
		$result->free();
		if ($buildingExists) {
			throw new Exception($this->_i18n->getMessage('stadiumenvironment_build_err_already_exists'));
		}
		
		// check required building
		if ($building['required_building_id']) {
			$result = $this->_db->querySelect('*', $dbPrefix . '_buildings_of_team', 'team_id = %d AND building_id = %d', array($teamId, $building['required_building_id']));
			$requiredBuildingExists = $result->fetch_array();
			$result->free();
			
			if (!$requiredBuildingExists) {
				throw new Exception($this->_i18n->getMessage('stadiumenvironment_build_err_requires_building'));
			}
		}
		
		// check premium costs
		if ($building['premiumfee'] > $user->premiumBalance) {
			throw new Exception($this->_i18n->getMessage('stadiumenvironment_build_err_premium_balance'));
		}
		
		// withdraw costs
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $teamId, $building['costs'], 
			'building_construction_fee_subject', $building['name']);
		
		// place order
		$constructionDeadline = $this->_websoccer->getNowAsTimestamp() + $building['construction_time_days'] * 24 * 3600;
		$this->_db->queryInsert(array(
				'building_id' => $buildingId,
				'team_id' => $teamId,
				'construction_deadline' => $constructionDeadline
				), $dbPrefix . '_buildings_of_team');
		
		// withdraw premium fee
		if ($building['premiumfee']) {
			PremiumDataService::debitAmount($this->_websoccer, $this->_db, $user->id, $building['premiumfee'], "order-building");
		}
		
		// credit fan popularity change
		if ($building['effect_fanpopularity'] != 0) {
			$result = $this->_db->querySelect('fanbeliebtheit', $dbPrefix . '_user', 'id = %d', $user->id, 1);
			$userinfo = $result->fetch_array();
			$result->free();
			
			$popularity = min(100, max(1, $building['effect_fanpopularity'] + $userinfo['fanbeliebtheit']));
			$this->_db->queryUpdate(array('fanbeliebtheit' => $popularity), $dbPrefix . '_user', 'id = %d', $user->id);
		}
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("stadiumenvironment_build_success"),
				""));
		
		return null;
	}
	
}

?>