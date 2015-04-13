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
 * Compute and update user inactivity info fields.
 * 
 * @author Ingo Hofmann
 */
class UserInactivityCheckJob extends AbstractJob {
	
	/**
	 * @see AbstractJob::execute()
	 */
	function execute() {
		
		// only consider highscore users since we assume that they are actually playing and not waiting for a team assignment or something.
		$users = UsersDataService::getActiveUsersWithHighscore($this->_websoccer, $this->_db, 0, 1000);
		foreach ($users as $user) {
			UserInactivityDataService::computeUserInactivity($this->_websoccer, $this->_db, $user['id']);
		}
		
	}
}

?>
