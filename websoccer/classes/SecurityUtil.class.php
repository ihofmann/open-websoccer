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
	 * Hashes a password using bcrypt (PHP password_hash).
	 *
	 * The salt parameter is retained for backward compatibility with existing
	 * call sites but is no longer used for new hashes — bcrypt generates its
	 * own internal salt.
	 *
	 * @param string $password unhashed password string.
	 * @param string $salt Salt (ignored for new bcrypt hashes).
	 * @return string bcrypt hash string.
	 */
	public static function hashPassword($password, $salt = '') {
		return password_hash($password, PASSWORD_BCRYPT);
	}
	
	/**
	 * Verifies a password against a stored hash, supporting both the legacy
	 * SHA-256 format and modern bcrypt hashes.
	 *
	 * @param string $password the plain-text password to check.
	 * @param string $salt the legacy salt (only used for legacy hashes).
	 * @param string $storedHash the hash stored in the database.
	 * @return bool TRUE if the password matches.
	 */
	public static function verifyPassword($password, $salt, $storedHash) {
		if (!is_string($storedHash) || $storedHash === '') {
			return FALSE;
		}
		// Modern bcrypt hash (starts with '$')
		if ($storedHash[0] === '$') {
			return password_verify($password, $storedHash);
		}
		// Legacy SHA-256 hash — use timing-safe comparison
		return hash_equals($storedHash, hash('sha256', $salt . hash('sha256', $password)));
	}
	
	/**
	 * Checks whether a stored hash should be rehashed to the modern format.
	 *
	 * @param string $storedHash the hash stored in the database.
	 * @return bool TRUE if the hash is in the legacy SHA-256 format.
	 */
	public static function needsRehash($storedHash) {
		if (!is_string($storedHash) || strlen($storedHash) === 0) {
			return TRUE;
		}
		// Legacy SHA-256 hashes are 64 hex chars and don't start with '$'
		return $storedHash[0] !== '$' || password_needs_rehash($storedHash, PASSWORD_BCRYPT);
	}
	
	/**
	 * Checks if current visitor is an authenticated admin user.
	 * 
	 * @return boolean TRUE if use is Admin.
	 */
	public static function isAdminLoggedIn() {
		// prevent session hijacking
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		if (isset($_SESSION['HTTP_USER_AGENT'])) {
			if (!hash_equals($_SESSION['HTTP_USER_AGENT'], hash('sha256', $userAgent))) {
				self::logoutAdmin();
				return FALSE;
			}
		} else {
			$_SESSION['HTTP_USER_AGENT'] = hash('sha256', $userAgent);
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
	 * Generates a random password using a cryptographically secure RNG.
	 * 
	 * @return string generated (unhashed) password of length 12.
	 */
	public static function generatePassword() {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$length = 12;
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[random_int(0, strlen($chars) - 1)];
		}
		return $result;
	}
	
	/**
	 * Generates a random salting string using a cryptographically secure RNG.
	 *
	 * The salt is 5 characters to fit the existing VARCHAR(5) database column.
	 * With bcrypt the external salt is no longer used — it is only kept for
	 * backward-compatible verification of legacy SHA-256 hashes.
	 *
	 * @return string salt string of length 5.
	 */
	public static function generatePasswordSalt() {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$result = '';
		for ($i = 0; $i < 5; $i++) {
			$result .= $chars[random_int(0, strlen($chars) - 1)];
		}
		return $result;
	}	
	
	/**
	 * Generates a cryptographically random token for identifying a user
	 * across sessions (e.g. "remember me" cookie).
	 * 
	 * @param int $userId User ID (ignored — token is fully random).
	 * @param string $salt password salt (ignored — token is fully random).
	 * @return string 64-character hex token.
	 */
	public static function generateSessionToken($userId, $salt) {
		return bin2hex(random_bytes(32));
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
