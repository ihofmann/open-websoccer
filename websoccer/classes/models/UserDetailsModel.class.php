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
 * Provides data of user with passed ID.
 */
class UserDetailsModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::renderView()
	 */
	public function renderView() {
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$userId = (int) $this->_websoccer->getRequestParameter('id');
		if ($userId < 1) {
			$userId = $this->_websoccer->getUser()->id;
		}
		
		$user = UsersDataService::getUserById($this->_websoccer, $this->_db, $userId);
		
		if (!isset($user['id'])) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		// get teams of user
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_verein';
		$whereCondition = 'user_id = %d AND status = \'1\' AND nationalteam != \'1\' ORDER BY name ASC';
		$result = $this->_db->querySelect('id,name', $fromTable, $whereCondition, $userId);		
		
		$teams = array();
		while ($team = $result->fetch_array()) {
			$teams[] = $team;
		}
		$result->free();
		
		// get national team of user
		if ($this->_websoccer->getConfig('nationalteams_enabled')) {
			$columns = 'id,name';
			$fromTable = $this->_websoccer->getConfig('db_prefix') . '_verein';
			$whereCondition = 'user_id = %d AND nationalteam = \'1\'';
			$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $userId, 1);
			$nationalteam = $result->fetch_array();
			$result->free();
			if (isset($nationalteam['id'])) {
				$user['nationalteam'] = $nationalteam;
			}
		}
		
		// badges
		$result = $this->_db->querySelect('name, description, level, date_rewarded, event', 
				$this->_websoccer->getConfig('db_prefix') . '_badge INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_badge_user ON id = badge_id', 
				'user_id = %d ORDER BY level DESC, date_rewarded ASC', $userId);
		$badges = array();
		while ($badge = $result->fetch_array()) {
			if (!isset($badges[$badge['event']])) {
				$badges[$badge['event']] = $badge;
			}
		}
		$result->free();
		
		return array('user' => $user, 'userteams' => $teams, 
				'absence' => AbsencesDataService::getCurrentAbsenceOfUser($this->_websoccer, $this->_db, $userId),
				'badges' => $badges);
	}
	
}

?>