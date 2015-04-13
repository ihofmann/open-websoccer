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
 * Deletes own youth match request.
 */
class CancelYouthMatchRequestController implements IActionController {
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
		if (!$this->_websoccer->getConfig("youth_enabled")) {
			return NULL;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// get request info
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_youthmatch_request";
		$result = $this->_db->querySelect("*", $fromTable, "id = %d", $parameters["id"]);
		$request = $result->fetch_array();
		$result->free();
		
		if (!$request) {
			throw new Exception($this->_i18n->getMessage("youthteam_matchrequest_cancel_err_notfound"));
		}
		
		// check if own request
		if ($clubId != $request["team_id"]) {
			throw new Exception("nice try");
		}
		
		// delte
		$this->_db->queryDelete($fromTable, "id = %d", $parameters["id"]);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("youthteam_matchrequest_cancel_success"),
				""));
		
		return "youth-matchrequests";
	}
	
}

?>