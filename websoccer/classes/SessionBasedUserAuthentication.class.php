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
define('SESSION_PARAM_USERID', 'frontuserid');

/**
 * Checks user's state by accessing the server session data.
 * This is the application's default user authentifaction.
 * 
 * @author Ingo Hofmann
 */
class SessionBasedUserAuthentication implements IUserAuthentication {
	private $_website;
	
	/**
	 * @param WebSoccer $website request context.
	 */
	public function __construct(WebSoccer $website) {
		$this->_website = $website;
	}
	
	/**
	 * @see IUserAuthentication::verifyAndUpdateCurrentUser()
	 */
	public function verifyAndUpdateCurrentUser(User $currentUser) {
		
		$db = DbConnection::getInstance();
		$fromTable = $this->_website->getConfig('db_prefix') .'_user';
		
		if (!isset($_SESSION[SESSION_PARAM_USERID]) || !$_SESSION[SESSION_PARAM_USERID]) {
			
			// 'remember me' token
			$rememberMe = CookieHelper::getCookieValue('user');
			if ($rememberMe != null) {
				
				$columns = 'id, passwort_salt, nick, email, lang';
				$whereCondition = 'status = 1 AND tokenid = \'%s\'';
				$result = $db->querySelect($columns, $fromTable, $whereCondition, $rememberMe);
				$rememberedUser = $result->fetch_array();
				$result->free();
				
				if (isset($rememberedUser['id'])) {
					
					$currentToken = SecurityUtil::generateSessionToken($rememberedUser['id'], $rememberedUser['passwort_salt']);
					if ($currentToken === $rememberMe) {
						$this->_login($rememberedUser, $db, $fromTable, $currentUser);
						return;
					} else {
						CookieHelper::destroyCookie('user');
						
						// invalid old token since most probably user agent changed
						$columns = array('tokenid' => '');
						$whereCondition = 'id = %d';
						$parameter = $rememberedUser['id'];
						$db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);
					}
					
				} else {
					CookieHelper::destroyCookie('user');
				}
				
				// user is neither in session nor with cookie logged on
			} else {
				return;
			}
		}
		
		// get user data
		$userid = (isset($_SESSION[SESSION_PARAM_USERID])) ? $_SESSION[SESSION_PARAM_USERID] : 0;
		if (!$userid) {
			return;
		}
		
		$columns = 'id, nick, email, lang, premium_balance, picture';
		$whereCondition = 'status = 1 AND id = %d';
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $userid);
		
		if ($result->num_rows) {
			
			$userdata = $result->fetch_array();
			$this->_login($userdata, $db, $fromTable, $currentUser);
			
		} else {
			// user might got disabled in the meanwhile
			$this->logoutUser($currentUser);
		}
		
		$result->free();
	}
	
	/**
	 * @see IUserAuthentication::logoutUser()
	 */
	public function logoutUser(User $currentUser) {
		if ($currentUser->getRole() == ROLE_USER) {
			$currentUser->id = null;
			$currentUser->username = null;
			$currentUser->email = null;
			
			$_SESSION = array();
			session_destroy();
			 
			CookieHelper::destroyCookie('user');
		}

	}
	
	private function _login($userdata, $db, $fromTable, $currentUser) {
		$_SESSION[SESSION_PARAM_USERID] = $userdata['id'];
		
		$currentUser->id = $userdata['id'];
		$currentUser->username = $userdata['nick'];
		$currentUser->email = $userdata['email'];
		$currentUser->lang = $userdata['lang'];
		$currentUser->premiumBalance = $userdata['premium_balance'];
		$currentUser->setProfilePicture($this->_website, $userdata['picture']);
		
		// update language
		$i18n = I18n::getInstance($this->_website->getConfig('supported_languages'));
		$i18n->setCurrentLanguage($userdata['lang']);
			
		// update timestamp of last action
		$columns = array('lastonline' => $this->_website->getNowAsTimestamp(), 'lastaction' => $this->_website->getRequestParameter('page'));
		$whereCondition = 'id = %d';
		$parameter = $userdata['id'];
		$db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);
	}
}

?>
