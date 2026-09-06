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
 * Default login method which hashes password and compares credentials with internal data base table.
 * Also supports sending a new password.
 * 
 * @author Ingo Hofmann
 */
class DefaultUserLoginMethod implements IUserLoginMethod {
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
		return $this->authenticate('UPPER(email)', strtoupper($email), $password);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUserLoginMethod::authenticateWithUsername()
	 */
	public function authenticateWithUsername($nick, $password) {
		return $this->authenticate('nick', $nick, $password);
	}
	
	private function authenticate($column, $loginstr, $password) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') .'_user';
		
		// get user data
		$columns = 'id, passwort, passwort_neu, passwort_salt';
		
		$wherePart = $column . ' = \'%s\' AND status = 1';
		$parameter = $loginstr;
		
		$result = $this->_db->querySelect($columns, $fromTable, $wherePart, $parameter);
		$userdata = $result->fetch_array();
		$result->free();
		
		// user does not exist
		if (!$userdata || !$userdata['id']) {
			return FALSE;
		}
		
		// check password against main password and new-password (reset)
		$matchesMain = SecurityUtil::verifyPassword($password, $userdata['passwort_salt'], $userdata['passwort']);
		$matchesNew = strlen($userdata['passwort_neu'] ?? '') > 0
			&& SecurityUtil::verifyPassword($password, $userdata['passwort_salt'], $userdata['passwort_neu']);
		if (!$matchesMain && !$matchesNew) {
			return FALSE;
		}
		
		// update password after a generated one
		if ($matchesNew) {
			$columns = array('passwort' => $userdata['passwort_neu'], 'passwort_neu_angefordert' => 0, 'passwort_neu' => '');
			$whereCondition = 'id = %d';
			$parameter = $userdata['id'];
			$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);
		}
		
		// rehash to bcrypt if still using legacy SHA-256
		if ($matchesMain && SecurityUtil::needsRehash($userdata['passwort'])) {
			$newHash = SecurityUtil::hashPassword($password);
			$this->_db->queryUpdate(array('passwort' => $newHash), $fromTable, 'id = %d', $userdata['id']);
		}
		
		return $userdata['id'];
	}
	
}

?>
