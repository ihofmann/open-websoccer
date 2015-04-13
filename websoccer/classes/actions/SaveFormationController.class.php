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
 * Saves the formation and its setup in a DB table.
 */
class SaveFormationController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	private $_addedPlayers;
	private $_isNationalTeam;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
		$this->_addedPlayers = array();
		$this->_isNationalTeam = ($websoccer->getRequestParameter('nationalteam')) ? TRUE : FALSE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		
		if ($this->_isNationalTeam) {
			$teamId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		} else {
			$teamId = $user->getClubId($this->_websoccer, $this->_db);
		}
		
		// check and get next match
		// next x matches
		$nextMatches = MatchesDataService::getNextMatches($this->_websoccer, $this->_db, $teamId,
				$this->_websoccer->getConfig('formation_max_next_matches'));
		if (!count($nextMatches)) {
			throw new Exception($this->_i18n->getMessage('formation_err_nonextmatch'));
		}
		
		// currently selected match
		$matchId = $parameters['id'];
		foreach ($nextMatches as $nextMatch) {
			if ($nextMatch['match_id'] == $matchId) {
				$matchinfo = $nextMatch;
				break;
			}
		}
		if (!isset($matchinfo)) {
			throw new Exception('illegal match id');
		}
		
		// get team players and check whether provided IDs are valid players (in team and not blocked)
		$players = PlayersDataService::getPlayersOfTeamById($this->_websoccer, $this->_db, $teamId, $this->_isNationalTeam, $matchinfo['match_type'] == 'cup', $matchinfo['match_type'] != 'friendly');
		$this->validatePlayer($parameters['player1'], $players);
		$this->validatePlayer($parameters['player2'], $players);
		$this->validatePlayer($parameters['player3'], $players);
		$this->validatePlayer($parameters['player4'], $players);
		$this->validatePlayer($parameters['player5'], $players);
		$this->validatePlayer($parameters['player6'], $players);
		$this->validatePlayer($parameters['player7'], $players);
		$this->validatePlayer($parameters['player8'], $players);
		$this->validatePlayer($parameters['player9'], $players);
		$this->validatePlayer($parameters['player10'], $players);
		$this->validatePlayer($parameters['player11'], $players);
		
		$this->validatePlayer($parameters['bench1'], $players, TRUE);
		$this->validatePlayer($parameters['bench2'], $players, TRUE);
		$this->validatePlayer($parameters['bench3'], $players, TRUE);
		$this->validatePlayer($parameters['bench4'], $players, TRUE);
		$this->validatePlayer($parameters['bench5'], $players, TRUE);
		
		// validate substitutions
		$validSubstitutions = array();
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			$playerIn = $parameters['sub' . $subNo .'_in'];
			$playerOut = $parameters['sub' . $subNo .'_out'];
			$playerMinute = $parameters['sub' . $subNo .'_minute'];
			if ($playerIn != null && $playerIn > 0 && $playerOut != null && $playerOut > 0 && $playerMinute != null && $playerMinute > 0) {
				$this->validateSubstitution($playerIn, $playerOut, $playerMinute, $players);
				$validSubstitutions[] = $subNo;
			}
		}
		
		// save formation
		$this->saveFormation($teamId, $matchinfo['match_id'], $parameters, $validSubstitutions);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage('saved_message_title'),
				''));
		
		return null;
	}
	
	private function validatePlayer($playerId, $players, $bench = FALSE) {
		if ($playerId == null || $playerId == 0) {
			return;
		}
		
		if (!isset($players[$playerId])) {
			throw new Exception($this->_i18n->getMessage('formation_err_invalidplayer'));
		}
		
		$position = $players[$playerId]['position'];
		
		if (isset($this->_addedPlayers[$position][$playerId])) {
			throw new Exception($this->_i18n->getMessage('formation_err_duplicateplayer'));
		}
		
		if ($players[$playerId]['matches_injured'] > 0 || $players[$playerId]['matches_blocked'] > 0) {
			throw new Exception($this->_i18n->getMessage('formation_err_blockedplayer'));
		}
		
		$this->_addedPlayers[$position][$playerId] = TRUE;
	}
	
	private function validateSubstitution($playerIn, $playerOut, $minute, $players) {
		
		if (!isset($players[$playerIn]) || !isset($players[$playerOut])
				|| !isset($this->_addedPlayers[$players[$playerIn]['position']][$playerIn]) 
				|| !isset($this->_addedPlayers[$players[$playerOut]['position']][$playerOut])) {
			throw new Exception($this->_i18n->getMessage('formation_err_invalidplayer'));
		}
		
		if ($minute < 2 || $minute > 90) {
			throw new Exception($this->_i18n->getMessage('formation_err_invalidsubstitutionminute'));
		}
		
	}
	
	private function saveFormation($teamId, $matchId, $parameters, $validSubstitutions) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') .'_aufstellung';
		
		$columns['verein_id'] = $teamId;
		$columns['datum'] = $this->_websoccer->getNowAsTimestamp();
		$columns['offensive'] = $parameters['offensive'];
		$columns['setup'] = $parameters['setup'];
		$columns['longpasses'] = $parameters['longpasses'];
		$columns['counterattacks'] = $parameters['counterattacks'];
		$columns['freekickplayer'] = $parameters['freekickplayer'];
		
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			$columns['spieler' . $playerNo] = $parameters['player' . $playerNo];
			$columns['spieler' . $playerNo . '_position'] = $parameters['player' . $playerNo . '_pos'];
		}
		
		for ($playerNo = 1; $playerNo <= 5; $playerNo++) {
			$columns['ersatz' . $playerNo] = $parameters['bench' . $playerNo];
		}
		
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if (in_array($subNo, $validSubstitutions)) {
				$columns['w'. $subNo . '_raus'] = $parameters['sub' . $subNo .'_out'];
				$columns['w'. $subNo . '_rein'] = $parameters['sub' . $subNo .'_in'];
				$columns['w'. $subNo . '_minute'] = $parameters['sub' . $subNo .'_minute'];
				$columns['w'. $subNo . '_condition'] = $parameters['sub' . $subNo .'_condition'];
				$columns['w'. $subNo . '_position'] = $parameters['sub' . $subNo .'_position'];
			} else {
				$columns['w'. $subNo . '_raus'] = '';
				$columns['w'. $subNo . '_rein'] = '';
				$columns['w'. $subNo . '_minute'] = '';
				$columns['w'. $subNo . '_condition'] = '';
				$columns['w'. $subNo . '_position'] = '';
			}
		}
		
		// update or insert?
		$result = $this->_db->querySelect('id', $fromTable, 'verein_id = %d AND match_id = %d', array($teamId, $matchId));
		$existingFormation = $result->fetch_array();
		$result->free();
		
		if (isset($existingFormation['id'])) {
			$this->_db->queryUpdate($columns, $fromTable, 'id = %d', $existingFormation['id']);
		} else {
			$columns['match_id'] = $matchId;
			$this->_db->queryInsert($columns, $fromTable);
		}
		
		// save as template
		if (strlen($parameters['templatename'])) {
			
			// count existing templates in order to stay below boundary
			$result = $this->_db->querySelect('COUNT(*) AS templates', $fromTable, 'verein_id = %d AND templatename IS NOT NULL', $teamId);
			$existingTemplates = $result->fetch_array();
			$result->free();
			
			if ($existingTemplates && $existingTemplates['templates'] >= $this->_websoccer->getConfig('formation_max_templates')) {
				$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING,
					$this->_i18n->getMessage('formation_template_saving_failed_because_boundary_title', $this->_websoccer->getConfig('formation_max_templates')),
					$this->_i18n->getMessage('formation_template_saving_failed_because_boundary_details')));
			} else {
				$columns['match_id'] = NULL;
				$columns['templatename'] = $parameters['templatename'];
				$this->_db->queryInsert($columns, $fromTable);
			}
			
		}
	}
	
}

?>