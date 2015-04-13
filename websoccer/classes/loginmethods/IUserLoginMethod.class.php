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
 * Any supported user login method must implement this interface.
 * A user signs in with either an e-mail address or nick name and his password.
 * This method must authenticate the user and also provides the in-game ID.
 * 
 * @author Ingo Hofmann
 */
interface IUserLoginMethod {
	
	/**
	 * @param WebSoccer $websoccer Application context.
	 */
	public function __construct(WebSoccer $websoccer, DbConnection $db);
	
	/**
	 * Authenticates a user. I.e. compares provided credentials with a desired data source and returns the internal user ID.
	 * If user signs in for the first time, a data record must be created in the internal data base.
	 * You can create a new user by calling UsersDataService::createLocalUser().
	 * 
	 * @param string $email By the user entered e-mail string (not escaped).
	 * @param string $password unhashed entered password (not escaped).
	 * @return ID of user in internal database in case user could be authenticated. FALSE if user could not be authenticated.
	 */
	public function authenticateWithEmail($email, $password);
	
	/**
	 * Authenticates a user. I.e. compares provided credentials with a desired data source and returns the internal user ID.
	 * If user signs in for the first time, a data record must be created in the internal data base.
	 * You can create a new user by calling UsersDataService::createLocalUser().
	 *
	 * @param string $nick By the user entered user name string (not escaped).
	 * @param string $password unhashed entered password (not escaped).
	 * @return ID of user in internal database in case user could be authenticated. FALSE if user could not be authenticated.
	 */
	public function authenticateWithUsername($nick, $password);
	
}

?>