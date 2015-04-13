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
 * Defines what a user authentication mechanism must implement.
 * 
 * @author Ingo Hofmann
 */
interface IUserAuthentication {
	
	/**
	 * @param WebSoccer $website request context.
	 */
	public function __construct(WebSoccer $website);
	
	/**
	 * Checks if the current user is authenticated and updates the user information accordingly.
	 * 
	 * @param User $currentUser instance of current user to be updated.
	 */
	public function verifyAndUpdateCurrentUser(User $currentUser);
	
	/**
	 * Invalidates the user session and sets user as guest by unsetting the user-ID.
	 * 
	 * @param User $currentUser
	 */
	public function logoutUser(User $currentUser);
	
}

?>