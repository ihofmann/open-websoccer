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
 * This enables the authentication over a locally installed Wordpress database.
 * 
 * @author Ingo Hofmann
 */
class WordpressUserLoginMethod implements IUserLoginMethod {
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
		return $this->_authenticate('LOWER(user_email) = \'%s\'', strtolower($email), $password);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUserLoginMethod::authenticateWithUsername()
	 */
	public function authenticateWithUsername($nick, $password) {
		return $this->_authenticate('user_login = \'%s\'', $nick, $password);
	}
	
	private function _authenticate($queryWhereCondition, $loginStr, $password) {
		
		// query user in Joomla table
		$result = $this->_db->querySelect('user_login,user_email,user_pass', 
				$this->_websoccer->getConfig('wordpresslogin_tableprefix') . 'users', 
				'user_status = 0 AND ' . $queryWhereCondition, $loginStr);
		$wpUser = $result->fetch_array();
		$result->free();
		
		// user does not exist
		if (!$wpUser) {
			return FALSE;
		}
		
		// check password.
		require(BASE_FOLDER . '/classes/phpass/PasswordHash.php');
		$hasher = new PasswordHash(8, TRUE );
		if (!$hasher->CheckPassword($password, $wpUser['user_pass'])) {
			return FALSE;
		}
		
		// valid user, check if he exists
		$userEmail = strtolower($wpUser['user_email']);
		$userId = UsersDataService::getUserIdByEmail($this->_websoccer, $this->_db, $userEmail);
		if ($userId > 0) {
			return $userId;
		}
		
		// create new user
		return UsersDataService::createLocalUser($this->_websoccer, $this->_db, $wpUser['user_login'], $userEmail);
	}
	
}

?>
