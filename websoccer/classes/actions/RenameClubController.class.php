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
 * Renames user's current club.
 */
class RenameClubController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		
		// check if feature is enabled
		if (!$this->_websoccer->getConfig('rename_club_enabled')) {
			throw new Exceltion("feature is disabled");
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		if (!$team) {
			return null;
		}
		
		// rename club
		$short = strtoupper($parameters['kurz']);
		$this->_db->queryUpdate(array('name' => $parameters['name'], 'kurz' => $short), 
				$this->_websoccer->getConfig('db_prefix') . '_verein',
				'id = %d', $clubId);
		
		// rename stadium
		$this->_db->queryUpdate(array('S.name' => $parameters['stadium']),
				$this->_websoccer->getConfig('db_prefix') . '_verein AS C INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_stadion AS S ON S.id = C.stadion_id',
				'C.id = %d', $clubId);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("rename-club_success"),
				""));
		
		return 'league';
	}
	
}

?>