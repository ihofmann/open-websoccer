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
define("SLEEP_SECONDS_ON_FAILURE", 5);
define("REMEMBERME_COOKIE_LIFETIME_DAYS", 30);

/**
 * Signs in user by either e-mail or user name and password.
 */
class FormLoginController implements IActionController {
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
		
		$loginMethodClass = $this->_websoccer->getConfig("login_method");
		if (!class_exists($loginMethodClass)) {
			throw new Exception("Login method class does not exist: " . $loginMethodClass);
		}
		
		$loginMethod = new $loginMethodClass($this->_websoccer, $this->_db);
		
		// sign in with e-mail
		if ($this->_websoccer->getConfig("login_type") == "email") {
			$userId = $loginMethod->authenticateWithEmail($parameters["loginstr"], $parameters["loginpassword"]);
			
			// sign in with user name
		} else {
			$userId = $loginMethod->authenticateWithUsername($parameters["loginstr"], $parameters["loginpassword"]);
		}
		
		// sign in failed
		if (!$userId) {
			sleep(SLEEP_SECONDS_ON_FAILURE);
			throw new Exception($this->_i18n->getMessage("formlogin_invalid_data"));
		}
		
		SecurityUtil::loginFrontUserUsingApplicationSession($this->_websoccer, $userId);
		
		// "remember me"
		if (isset($parameters["rememberme"]) && $parameters["rememberme"] == 1) {
			
			$fromTable = $this->_websoccer->getConfig("db_prefix") . "_user";
			$whereCondition = "id = %d";
			$parameter = $userId;
			
			// get password salt
			$result = $this->_db->querySelect("passwort_salt", $fromTable, $whereCondition, $parameter);
			$saltinfo = $result->fetch_array();
			$result->free();
			
			$salt = $saltinfo["passwort_salt"];
			if (!strlen($salt)) {
				$salt = SecurityUtil::generatePasswordSalt();
			}
			
			$sessionToken = SecurityUtil::generateSessionToken($userId, $salt);
			$columns = array("tokenid" => $sessionToken, "passwort_salt" => $salt);
			
			$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);
			
			CookieHelper::createCookie("user", $sessionToken, REMEMBERME_COOKIE_LIFETIME_DAYS);
		}
		
		return (strlen($this->_websoccer->getUser()->username)) ? "office" : "enter-username";
	}
	
}

?>