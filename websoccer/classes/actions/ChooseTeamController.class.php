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

class ChooseTeamController implements IActionController {
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
		
		// check whether featue is enabled
		if (!$this->_websoccer->getConfig("assign_team_automatically")) {
			throw new Exception($this->_i18n->getMessage("freeclubs_msg_error"));
		}
		
		if ($user->getClubId($this->_websoccer, $this->_db) > 0) {
			throw new Exception($this->_i18n->getMessage("freeclubs_msg_error_user_is_already_manager"));
		}
		
		$teamId = $parameters["teamId"];
		
		// check whether club still has no manager
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_verein";
		$whereCondition = "id = %d AND status = 1 AND (user_id = 0 OR user_id IS NULL OR interimmanager = '1')";
		$result = $this->_db->querySelect("id", $fromTable, $whereCondition, $teamId);
		$club = $result->fetch_array();
		$result->free();
		
		if (!isset($club["id"])) {
			throw new Exception($this->_i18n->getMessage("freeclubs_msg_error"));
		}
		
		$columns = array();
		$columns["user_id"] = $user->id;
		$columns["interimmanager"] = "0";
		
		// update record
		if (count($columns)) {
			$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $teamId);
		}
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("freeclubs_msg_success"),
				""));
		
		return "office";
	}
	
}

?>