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

define('COOKIE_PREFIX', 'ws');

/**
 * Helps handling cookies.
 * 
 * @author Ingo Hofmann
 */
class CookieHelper {

	/**
	 * Creates new cookie.
	 * 
	 * @param string $name cookie name (without application prefix).
	 * @param string $value cookie value.
	 * @param string|NULL $lifetimeInDays lifetime in days or NULL if it shall expire after user session.
	 */
	public static function createCookie($name, $value, $lifetimeInDays = null) {
		$expiry = ($lifetimeInDays != null) ? time() + 86400 * $lifetimeInDays : 0;
		
		setcookie(COOKIE_PREFIX . $name, $value, $expiry);
	}
	
	/**
	 * 
	 * @param string $name cookie name (without application prefix)
	 * @return NULL|sring Cookie value or NULL if there is no cookie with specified name.
	 */
	public static function getCookieValue($name) {
		if (!isset($_COOKIE[COOKIE_PREFIX . $name])) {
			return null;
		}
		
		return $_COOKIE[COOKIE_PREFIX . $name];
	}
	
	/**
	 * Deletes cookie with specified name.
	 * 
	 * @param string $name cookie name  (without application prefix).
	 */
	public static function destroyCookie($name) {
		if (!isset($_COOKIE[COOKIE_PREFIX . $name])) {
			return;
		}
	
		setcookie(COOKIE_PREFIX . $name, '', time()-86400);
	}
	
}
?>