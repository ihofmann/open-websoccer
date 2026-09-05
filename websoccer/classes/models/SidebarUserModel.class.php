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
 * Provides user/team data for the left navigation sidebar.
 *
 * Replaces the former "My Profile" sidebar block. Team name, budget,
 * premium balance, unread messages and notifications are now shown
 * directly in the sidebar rail rather than in a separate box.
 */
class SidebarUserModel implements IModel {
	private $_db;
	private $_websoccer;

	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_websoccer = $websoccer;
	}

	public function renderView() {
		return $this->_websoccer->getUser()->id !== null;
	}

	public function getTemplateParameters() {
		$user = $this->_websoccer->getUser();
		$clubId = $user->getClubId($this->_websoccer, $this->_db);

		$team = null;
		if ($clubId > 0) {
			$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		}

		$unseenMessages = MessagesDataService::countUnseenInboxMessages($this->_websoccer, $this->_db);
		$unseenNotifications = NotificationsDataService::countUnseenNotifications(
			$this->_websoccer, $this->_db, $user->id, $clubId);

		return array(
			'sidebarUserteam' => $team,
			'sidebarUnseenMessages' => $unseenMessages,
			'sidebarUnseenNotifications' => $unseenNotifications,
		);
	}
}
