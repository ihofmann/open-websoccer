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
 * @author Ingo Hofmann
 */
class ProfileBlockModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return (strlen($this->_websoccer->getUser()->username)) ? TRUE : FALSE;
	}
	
	public function getTemplateParameters() {
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_user";
		
		$user = $this->_websoccer->getUser();
		
		// select general information
		$columns = "fanbeliebtheit AS user_popularity, highscore AS user_highscore";
		$whereCondition = "id = %d";
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $user->id, 1);
		$userinfo = $result->fetch_array();
		$result->free();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// get team info
		$team = null;
		if ($clubId > 0) {
			$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		}
		
		// unread messages
		$unseenMessages = MessagesDataService::countUnseenInboxMessages($this->_websoccer, $this->_db);
		
		// unseen notifications
		$unseenNotifications = NotificationsDataService::countUnseenNotifications($this->_websoccer, $this->_db, $user->id, $clubId);
		
		return array("profile" => $userinfo, "userteam" => $team, "unseenMessages" => $unseenMessages,
				"unseenNotifications" => $unseenNotifications);
	}
	
	
}

?>