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
 * Disables own user's account.
 */
class DisableAccountController implements IActionController {
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
		
		// fire user
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		if ($clubId) {
			$this->_db->queryUpdate(array("user_id" => '', "captain_id" => ''), $this->_websoccer->getConfig("db_prefix") . "_verein", 
					"user_id = %d", $this->_websoccer->getUser()->id);
		}
		
		// disable user
		$this->_db->queryUpdate(array("status" => "0"), $this->_websoccer->getConfig("db_prefix") . "_user",
				"id = %d", $this->_websoccer->getUser()->id);
		
		// logout user
		$authenticatorClasses = explode(",", $this->_websoccer->getConfig("authentication_mechanism"));
		foreach ($authenticatorClasses as $authenticatorClass) {
			$authenticatorClass = trim($authenticatorClass);
			if (!class_exists($authenticatorClass)) {
				throw new Exception("Class not found: " . $authenticatorClass);
			}
			$authenticator = new $authenticatorClass($this->_websoccer);
			$authenticator->logoutUser($this->_websoccer->getUser());
		}
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, $this->_i18n->getMessage("cancellation_success"),
				""));
		
		return "home";
	}
	
}

?>