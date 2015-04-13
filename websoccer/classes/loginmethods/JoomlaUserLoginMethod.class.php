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
 * This enables the authentication over a locally installed Joomla database.
 * 
 * @author Ingo Hofmann
 */
class JoomlaUserLoginMethod implements IUserLoginMethod {
	private $_websoccer;
	private $_db;
	
	/**
	 * Creates instance.
	 * @param WebSoccer $website Application context.
	 * @param DbConnection $db DB connection.
	 */
	public function __construct(WebSoccer $website, DbConnection $db) {
		$this->_websoccer = $website;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUserLoginMethod::authenticateWithEmail()
	 */
	public function authenticateWithEmail($email, $password) {
		return $this->_authenticate('email = \'%s\'', $email, $password);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUserLoginMethod::authenticateWithUsername()
	 */
	public function authenticateWithUsername($nick, $password) {
		return $this->_authenticate('username = \'%s\'', $nick, $password);
	}
	
	private function _authenticate($queryWhereCondition, $loginStr, $password) {
		
		// query user in Joomla table
		$result = $this->_db->querySelect('username,email,password', 
				$this->_websoccer->getConfig('joomlalogin_tableprefix') . 'users', 
				'block < 1 AND ' . $queryWhereCondition, $loginStr);
		$joomlaUser = $result->fetch_array();
		$result->free();
		
		// user does not exist
		if (!$joomlaUser) {
			return FALSE;
		}
		
		// check password. Joomla password has two parts: 0. password hash; 1. salt
		$passwordParts = explode(':', $joomlaUser['password']);
		$hashedPassword = md5($password . $passwordParts[1]);
		if ($hashedPassword != $passwordParts[0]) {
			return FALSE;
		}
		
		// valid user, check if he exists
		$userEmail = strtolower($joomlaUser['email']);
		$userId = UsersDataService::getUserIdByEmail($this->_websoccer, $this->_db, $userEmail);
		if ($userId > 0) {
			return $userId;
		}
		
		// create new user
		return UsersDataService::createLocalUser($this->_websoccer, $this->_db, $joomlaUser['username'], $userEmail);
	}
	
}

?>
