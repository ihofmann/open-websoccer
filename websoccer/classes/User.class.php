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
define('ROLE_GUEST', 'guest');
define('ROLE_USER', 'user');

define('USER_STATUS_ENABLED', 1);
define('USER_STATUS_UNCONFIRMED', 2);

/**
 * The current request's user.
 * 
 * @author Ingo Hofmann
 */
class User {
	
	/**
	 * @var int user id, if logged on.
	 */
	public $id;
	
	/**
	 * @var string user name, if logged on.
	 */
	public $username;
	
	/**
	 * @var string user e-mail, if logged on.
	 */
	public $email;
	
	/**
	 * @var string selected language.
	 */
	public $language;
	
	/**
	 * @var int Premium account balance. 0 if no balance available or account is empty.
	 */
	public $premiumBalance;
	
	private $_clubId;
	private $_profilePicture;
	private $_isAdmin;
	
	/**
	 * creates empty instance.
	 */
	public function __construct() {
		$this->premiumBalance = 0;
		$this->_isAdmin = NULL;
	}
	
	/**
	 * @return string user's permission role. That is 'guest' by default and 'user' in case the ID is not NULL.
	 */
	public function getRole() {
		if ($this->id == null) {
			return ROLE_GUEST;
		} else {
			return ROLE_USER;
		}
	}
	
	/**
	 * Get user's selected club ID. If user is manager of several clubs, the first club is selected by default .
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db database connection.
	 * @return int|NULL user's selected Club ID or NULL if user is no manager of any club.
	 */
	public function getClubId($websoccer = null, $db = null) {
		if ($this->id != null && $this->_clubId == null) {
			
			// get from session
			if (isset($_SESSION['clubid'])) {
				$this->_clubId = $_SESSION['clubid'];
			} else if ($websoccer != null && $db != null) {
				
				// default implementation: get first available club which is not managed as interim manager (user might have several clubs)
				$fromTable = $websoccer->getConfig('db_prefix') . '_verein';
				$whereCondition = 'status = 1 AND user_id = %d AND nationalteam != \'1\' ORDER BY interimmanager DESC';
				$columns = 'id';
				$result = $db->querySelect($columns, $fromTable, $whereCondition, $this->id, 1);
				$club = $result->fetch_array();
				$result->free();
				
				if ($club) {
					$this->setClubId($club['id']);
				}
			}

		}
		return $this->_clubId;
	}
	
	/**
	 * Set selected club ID. Will be stored in user's session.
	 * 
	 * @param int $clubId club ID to set.
	 */
	public function setClubId($clubId) {
		$_SESSION['clubid'] = $clubId;
		$this->_clubId = $clubId;
	}
	
	/**
	 * Provides the user's profile picture, if one is configured.
	 * 
	 * @return string URL to picture or empty string if no picture is available.
	 */
	public function getProfilePicture() {
		if ($this->_profilePicture == null) {
			if (strlen($this->email)) {
				$this->_profilePicture = UsersDataService::getUserProfilePicture(WebSoccer::getInstance(), null, $this->email);
			} else {
				$this->_profilePicture = '';
			}
		}
			
		return $this->_profilePicture;
	}
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param String $fileName file name of picture.
	 */
	public function setProfilePicture(WebSoccer $websoccer, $fileName) {
		if (strlen($fileName)) {
			$this->_profilePicture = UsersDataService::getUserProfilePicture($websoccer, $fileName, null);
		}
		
	}
	
	/**
	 * Is current user also a registered admin user? Will be determined by e-mail address.
	 * 
	 * @return boolean TRUE if user is an admin user, FALSE otherwise.
	 */
	public function isAdmin() {
		if ($this->_isAdmin === NULL) {
			
			$websoccer = WebSoccer::getInstance();
			$db = DbConnection::getInstance();
			
			$result = $db->querySelect('id', $websoccer->getConfig('db_prefix') . '_admin',
					'email = \'%s\' AND r_admin = \'1\'', $this->email);
			if ($result->num_rows) {
				$this->_isAdmin = TRUE;
			} else {
				$this->_isAdmin = FALSE;
			}
			$result->free();
		}
		
		return $this->_isAdmin;
	}
}

?>
