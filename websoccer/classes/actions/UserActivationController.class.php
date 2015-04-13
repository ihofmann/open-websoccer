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

class UserActivationController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		$key = $parameters["key"];
		$userid = $parameters["userid"];
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_user";
		
		// get user
		$columns = "id";
		$wherePart = "schluessel = '%s' AND id = %d AND status = 2";
		$parameters = array($key, $userid);
		$result = $this->_db->querySelect($columns, $fromTable, $wherePart, $parameters);
		$userdata = $result->fetch_array();
		$result->free();
		
		if (!isset($userdata["id"])) {
			sleep(5);
			throw new Exception($this->_i18n->getMessage("activate-user_user-not-found"));
		}
		
		// update user
		$columns = array("status" => 1);
		$whereCondition = "id = %d";
		$parameter = $userdata["id"];
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);
		
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("activate-user_message_title"),
				$this->_i18n->getMessage("activate-user_message_content")));
		
		return null;
	}
	
}

?>