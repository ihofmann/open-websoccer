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
 * Callback controller for Google+ OAuth authentication.
 */
class GoogleplusLoginController implements IActionController {
	private $_i18n;
	private $_db;
	private $_websoccer;
	
	/**
	 * Creates new instance.
	 * @param I18n $i18n Messages context.
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 */
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
		
		// authenticate
		$userEmail = GoogleplusSdk::getInstance($this->_websoccer)->authenticateUser();
		
		// not authenticated
		if (!$userEmail) {
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING,
					$this->_i18n->getMessage("googlepluslogin_failure"),
					""));
			return "home";
		}
		
		// authenticated. Check if user exists.
		$userEmail = strtolower($userEmail);
		$userId = UsersDataService::getUserIdByEmail($this->_websoccer, $this->_db, $userEmail);
		
		// if does not exist, then create new user
		if ($userId < 1) {
			$userId = UsersDataService::createLocalUser($this->_websoccer, $this->_db, null, $userEmail);
		}
		
		// log in user
		SecurityUtil::loginFrontUserUsingApplicationSession($this->_websoccer, $userId);
		
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("googlepluslogin_success"),
				""));
		
		return (strlen($this->_websoccer->getUser()->username)) ? "office" : "enter-username";
	}
	
}

?>