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
 * If current stadium construction is due, check if completed and update stadium. Or pospone deadline, depending on
 * builder's reliability.
 */
class AcceptStadiumConstructionWorkController implements IActionController {
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
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// verify that it is due
		$construction = StadiumsDataService::getCurrentConstructionOrderOfTeam($this->_websoccer, $this->_db, $clubId);
		if ($construction == NULL || $construction["deadline"] > $this->_websoccer->getNowAsTimestamp()) {
			throw new Exception($this->_i18n->getMessage("stadium_acceptconstruction_err_nonedue"));
		}
		
		// is completed?
		$pStatus["completed"] = $construction["builder_reliability"];
		$pStatus["notcompleted"] = 100 - $pStatus["completed"];
		$constructionResult = SimulationHelper::selectItemFromProbabilities($pStatus);
		
		// not completed: postpone deadline
		if ($constructionResult == "notcompleted") {
			
			$newDeadline = $this->_websoccer->getNowAsTimestamp() + $this->_websoccer->getConfig("stadium_construction_delay") * 24 * 3600;
			$this->_db->queryUpdate(array("deadline" => $newDeadline), $this->_websoccer->getConfig("db_prefix") . "_stadium_construction", 
					"id = %d", $construction["id"]);
			
			// show warning alert
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING,
					$this->_i18n->getMessage("stadium_acceptconstruction_notcompleted_title"),
					$this->_i18n->getMessage("stadium_acceptconstruction_notcompleted_details")));
			
			// completed
		} else {
			
			// update stadium
			$stadium = StadiumsDataService::getStadiumByTeamId($this->_websoccer, $this->_db, $clubId);
			$columns = array();
			$columns["p_steh"] = $stadium["places_stands"] + $construction["p_steh"];
			$columns["p_sitz"] = $stadium["places_seats"] + $construction["p_sitz"];
			$columns["p_haupt_steh"] = $stadium["places_stands_grand"] + $construction["p_haupt_steh"];
			$columns["p_haupt_sitz"] = $stadium["places_seats_grand"] + $construction["p_haupt_sitz"];
			$columns["p_vip"] = $stadium["places_vip"] + $construction["p_vip"];
			$this->_db->queryUpdate($columns, $this->_websoccer->getConfig("db_prefix") . "_stadion", "id = %d", 
					$stadium["stadium_id"]);
			
			// delete order
			$this->_db->queryDelete($this->_websoccer->getConfig("db_prefix") . "_stadium_construction", 
					"id = %d", $construction["id"]);
			
			// create success message
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
					$this->_i18n->getMessage("stadium_acceptconstruction_completed_title"),
					$this->_i18n->getMessage("stadium_acceptconstruction_completed_details")));
		}
		
		return null;
	}
	
}

?>