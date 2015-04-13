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
 * Assigns an additional club to user who already managesat least one club.
 */
class ChooseAdditionalTeamController implements IActionController {
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
		
		$user = $this->_websoccer->getUser();
		
		// check whether feature is enabled
		$maxTeams = (int) $this->_websoccer->getConfig('max_number_teams_per_user');
		if (!$this->_websoccer->getConfig('assign_team_automatically') && $maxTeams > 1) {
			throw new Exception($this->_i18n->getMessage('freeclubs_msg_error'));
		}
		
		// check minimum highscore
		$minHighscore = (int) $this->_websoccer->getConfig('additional_team_min_highscore');
		if ($minHighscore) {
			$result = $this->_db->querySelect('highscore', $this->_websoccer->getConfig('db_prefix') . '_user',
					'id = %d', $user->id);
			$userinfo = $result->fetch_array();
			$result->free();
			
			if ($minHighscore > $userinfo['highscore']) {
				throw new Exception($this->_i18n->getMessage('freeclubs_msg_error_minhighscore', $minHighscore));
			}
		}
		
		// check maximum number of teams per user
		$fromTable = $this->_websoccer->getConfig('db_prefix') .'_verein';
		$result = $this->_db->querySelect('id,liga_id', $fromTable, 'user_id = %d', $user->id);
		$teamsOfUser = array();
		while ($teamOfUser = $result->fetch_array()) {
			$teamsOfUser[$teamOfUser['liga_id']][] = $teamOfUser['id'];
		}
		$result->free_result();
		
		if (count($teamsOfUser) >= $this->_websoccer->getConfig('max_number_teams_per_user')) {
			throw new Exception($this->_i18n->getMessage('freeclubs_msg_error_max_number_of_teams', $maxTeams));
		}
		
		$teamId = $parameters['teamId'];
		
		// check whether club still has no manager
		$result = $this->_db->querySelect('id,user_id,liga_id,interimmanager', $fromTable, 'id = %d AND status = 1', $teamId);
		$club = $result->fetch_array();
		$result->free();
		
		if ($club['user_id'] && !$club['interimmanager']) {
			throw new Exception($this->_i18n->getMessage('freeclubs_msg_error'));
		}
		
		// user may not pick a club from the same league
		if (isset($teamsOfUser[$club['liga_id']])) {
			throw new Exception($this->_i18n->getMessage('freeclubs_msg_error_no_club_from_same_league'));
		}
		
		// update record
		$this->_db->queryUpdate(array('user_id' => $user->id), $fromTable, "id = %d", $teamId);
		
		$user->setClubId($teamId);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage('freeclubs_msg_success'),
				''));
		
		return 'office';
	}
	
}

?>