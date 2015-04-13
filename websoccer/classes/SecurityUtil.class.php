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
 * Util class for user security processes.
 * 
 * @author Ingo Hofmann
 */
class SecurityUtil {
	
	/**
	 * Hashes a password.
	 * 
	 * @param string $password unhashed password string.
	 * @param string $salt Salt to add to the password.
	 * @return string hashed password, including salt.
	 */
	public static function hashPassword($password, $salt) {
		return hash('sha256', $salt . hash('sha256', $password));
	}
	
	/**
	 * Checks if current visitor is an authenticated admin user.
	 * 
	 * @return boolean TRUE if use is Admin.
	 */
	public static function isAdminLoggedIn() {
		// prevent session hijacking
		if (isset($_SESSION['HTTP_USER_AGENT'])) {
			if ($_SESSION['HTTP_USER_AGENT'] != md5($_SERVER['HTTP_USER_AGENT'])) {
				self::logoutAdmin();
				return FALSE;
			}
		} else {
			$_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT']);
		}
	
	    return (isset($_SESSION['valid']) && $_SESSION['valid']);
	}
	
	/**
	 * Loggs off an admin user by destroying the whole session.
	 */
	public static function logoutAdmin() {
	    $_SESSION = array();
	    session_destroy();
	}
	
	/**
	 * Generates a random password.
	 * 
	 * @return string generated (unhashed) password of length 8.
	 */
	public static function generatePassword() {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789%!=?';
		return substr(str_shuffle($chars), 0, 8);
	}
	
	/**
	 * Generates a random salting string.
	 * 
	 * @return string salt string of length 4.
	 */
	public static function generatePasswordSalt() {
		return substr(self::generatePassword(), 0, 4);
	}	
	
	/**
	 * Generates a token that can be stored in the session or cookie in order to identify a user.
	 * 
	 * @param int $userId User ID
	 * @param string $salt password salt.
	 * @return string generated session token.
	 */
	public static function generateSessionToken($userId, $salt) {
		
		$useragent = (isset($_SESSION['HTTP_USER_AGENT'])) ? $_SESSION['HTTP_USER_AGENT'] : 'n.a.';
		
		return md5($salt . $useragent . $userId);
	}
	
	/**
	 * Stores ID of user in session and triggers authentication by SessionBasedUserAuthentication.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param int $userId ID of user to log in.
	 */
	public static function loginFrontUserUsingApplicationSession(WebSoccer $websoccer, $userId) {
		
		// actual log-in
		$_SESSION['frontuserid'] = $userId;
		session_regenerate_id();
		
		// update user data
		$userProvider = new SessionBasedUserAuthentication($websoccer);
		$userProvider->verifyAndUpdateCurrentUser($websoccer->getUser());
		
	}
}

?>
