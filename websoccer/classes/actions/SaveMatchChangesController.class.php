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
 * Saves tactic changes during a match.
 */
class SaveMatchChangesController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	private $_addedPlayers;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
		$this->_addedPlayers = array();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		$nationalTeamId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		$matchId = $parameters['id'];
		
		// check and get match data
		$matchinfo = MatchesDataService::getMatchSubstitutionsById($this->_websoccer, $this->_db, $matchId);
		if (!isset($matchinfo['match_id'])) {
			throw new Exception($this->_i18n->getMessage('formation_err_nonextmatch'));
		}
		
		// check whether user is one of the team managers
		if ($matchinfo['match_home_id'] != $teamId && $matchinfo['match_guest_id'] != $teamId
				&& $matchinfo['match_home_id'] != $nationalTeamId && $matchinfo['match_guest_id'] != $nationalTeamId) {
			throw new Exception('nice try');
		}
		
		// is already completed?
		if ($matchinfo['match_simulated']) {
			throw new Exception($this->_i18n->getMessage('match_details_match_completed'));
		}
		
		// update match fields
		$columns = array();
		$teamPrefix = ($matchinfo['match_home_id'] == $teamId || $matchinfo['match_home_id'] == $nationalTeamId) ? 'home' : 'guest';
		$teamPrefixDb = ($matchinfo['match_home_id'] == $teamId || $matchinfo['match_home_id'] == $nationalTeamId) ? 'home' : 'gast';
		
		// consider already executed subs
		$occupiedSubPos = array();
		$existingFutureSubs = array();
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			$existingMinute = (int) $matchinfo[$teamPrefix . '_sub'. $subNo . '_minute'];
			
			if ($existingMinute > 0 && $existingMinute <= $matchinfo['match_minutes']) {
				$occupiedSubPos[$subNo] = TRUE;
			} elseif ($existingMinute > 0) {
				$existingFutureSubs[$matchinfo[$teamPrefix . '_sub'. $subNo . '_out']] = array(
						'minute' => $matchinfo[$teamPrefix . '_sub'. $subNo . '_minute'],
						'in' => $matchinfo[$teamPrefix . '_sub'. $subNo . '_in'],
						'condition' => $matchinfo[$teamPrefix . '_sub'. $subNo . '_condition'],
						'position' => $matchinfo[$teamPrefix . '_sub'. $subNo . '_position'],
						'slot' => $subNo
						);
			}
			
		}
		
		// save subs
		if (count($occupiedSubPos) < 3) {
			// a substitution must be announced at least number of minutes of interval, otherwise no chance of execution
			$nextPossibleMinute = $matchinfo['match_minutes'] + $this->_websoccer->getConfig('sim_interval') + 1;
			
			for ($subNo = 1; $subNo <= 3; $subNo++) {
				$newOut = (int) $parameters['sub'. $subNo . '_out'];
				$newIn = (int) $parameters['sub'. $subNo . '_in'];
				$newMinute = (int) $parameters['sub'. $subNo . '_minute'];
				$newCondition = $parameters['sub'. $subNo . '_condition'];
				$newPosition = $parameters['sub'. $subNo . '_position'];
				
				$slot = FALSE;
				$saveSub = TRUE;
				
				// replace existing sub
				if (isset($existingFutureSubs[$newOut]) && $newIn == $existingFutureSubs[$newOut]['in']
							&& $newCondition == $existingFutureSubs[$newOut]['condition']
							&& $newMinute == $existingFutureSubs[$newOut]['minute']
							&& $newPosition == $existingFutureSubs[$newOut]['position']) {
						$saveSub = FALSE;
				}
				
				// get first free slot
				for ($slotNo = 1; $slotNo <= 3; $slotNo++) {
					if (!isset($occupiedSubPos[$slotNo])) {
						$slot = $slotNo;
						break;
					}
				}
				
				
				if ($slot && $newOut && $newIn && $newMinute) {
					if ($saveSub && $newMinute < $nextPossibleMinute) {
						$newMinute = $nextPossibleMinute;
						$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING,
								'',
								$this->_i18n->getMessage('match_details_changes_too_late_altered', $subNo)));
					}
					
					$columns[$teamPrefixDb . '_w'. $slot. '_raus'] = $newOut;
					$columns[$teamPrefixDb . '_w'. $slot. '_rein'] = $newIn;
					$columns[$teamPrefixDb . '_w'. $slot. '_minute'] = $newMinute;
					$columns[$teamPrefixDb . '_w'. $slot. '_condition'] = $newCondition;
					$columns[$teamPrefixDb . '_w'. $slot. '_position'] = $newPosition;
					
					$occupiedSubPos[$slot] = TRUE;
				}
			}
		}
		
		// update tactics
		$prevOffensive = $matchinfo['match_'. $teamPrefix .'_offensive'];
		$prevLongpasses = $matchinfo['match_'. $teamPrefix .'_longpasses'];
		$prevCounterattacks = $matchinfo['match_'. $teamPrefix .'_counterattacks'];
		if (!$prevLongpasses) {
			$prevLongpasses = '0';
		}
		if (!$prevCounterattacks) {
			$prevCounterattacks = '0';
		}
		if ($prevOffensive !== $parameters['offensive']
				|| $prevLongpasses !== $parameters['longpasses']
				|| $prevCounterattacks !== $parameters['counterattacks']) {
			
			$alreadyChanged = $matchinfo['match_'. $teamPrefix .'_offensive_changed'];
			if ($alreadyChanged >= $this->_websoccer->getConfig('sim_allow_offensivechanges')) {
				throw new Exception($this->_i18n->getMessage('match_details_changes_too_often', 
						$this->_websoccer->getConfig('sim_allow_offensivechanges')));
			}
			
			$columns[$teamPrefixDb .'_offensive'] = $parameters['offensive'];
			$columns[$teamPrefixDb .'_longpasses'] = $parameters['longpasses'];
			$columns[$teamPrefixDb .'_counterattacks'] = $parameters['counterattacks'];
			$columns[$teamPrefixDb .'_offensive_changed'] = $alreadyChanged + 1;
			
			$this->_createMatchReportMessage($user, $matchId, $matchinfo['match_minutes'], ($teamPrefix == 'home'));
		}
		
		// free kick taker
		$prevFreekickPlayer = $matchinfo['match_'. $teamPrefix .'_freekickplayer'];
		if ($parameters['freekickplayer'] && $parameters['freekickplayer'] != $prevFreekickPlayer) {
			$columns[$teamPrefixDb .'_freekickplayer'] = $parameters['freekickplayer'];
		}
		
		// execute update
		if (count($columns)) {
			$fromTable = $this->_websoccer->getConfig('db_prefix') . '_spiel';
			$whereCondition = 'id = %d';
			
			$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $matchId);
		}
		
		$this->_updatePlayerPosition($parameters, $matchId, $teamId);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage('saved_message_title'),
				''));
		
		return "match";
	}
	
	private function _updatePlayerPosition($parameters, $matchId, $teamId) {
		
		$players = MatchesDataService::getMatchPlayerRecordsByField($this->_websoccer, $this->_db, $matchId, $teamId);
		$playersOnField = $players['field'];
		
		// read submitted player positions
		$submittedPositions = array();
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			$playerId = $parameters['player' . $playerNo];
			$playerPos = $parameters['player' . $playerNo . '_pos'];
			if ($playerId && $playerPos) {
				$submittedPositions[$playerId] = $playerPos;
			}
		}
		
		$updateTable = $this->_websoccer->getConfig('db_prefix') . '_spiel_berechnung';
		$whereCondition = 'id = %d';
		
		$setupMainMapping = array(
				'T' => 'Torwart',
				'LV' => 'Abwehr',
				'RV' => 'Abwehr',
				'IV' => 'Abwehr',
				'DM' => 'Mittelfeld',
				'LM' => 'Mittelfeld',
				'ZM' => 'Mittelfeld',
				'RM' => 'Mittelfeld',
				'OM' => 'Mittelfeld',
				'LS' => 'Sturm',
				'MS' => 'Sturm',
				'RS' => 'Sturm');
		
		foreach ($playersOnField as $player) {
			if (isset($submittedPositions[$player['id']])) {
				
				$newPos = $submittedPositions[$player['id']];
				$oldPos = $player['match_position_main'];
				
				if ($newPos != $oldPos) {
					$position = $setupMainMapping[$newPos];
					
					// recompute strength
					$strength = $player['strength'];
					
					// player becomes weaker: wrong position
					if ($player['position'] != $position 
							&& $player['position_main'] != $newPos 
							&& $player['position_second'] != $newPos) {
						$strength = round($strength * (1 - $this->_websoccer->getConfig('sim_strength_reduction_wrongposition') / 100));
						
						// player becomes weaker: secondary position
					} elseif (strlen($player['position_main']) && $player['position_main'] != $newPos &&
							($player['position'] == $position || $player['position_second'] == $newPos)) {
						$strength = round($strength * (1 - $this->_websoccer->getConfig('sim_strength_reduction_secondary') / 100));
					}
					
					$this->_db->queryUpdate(array('position_main' => $newPos, 'position' => $position, 'w_staerke' => $strength), 
							$updateTable, $whereCondition, $player['match_record_id']);
				}
			}
		}
		
	}
	
	private function _createMatchReportMessage(User $user, $matchId, $minute, $isHomeTeam) {
		
		// get available messages
		$result = $this->_db->querySelect('id', $this->_websoccer->getConfig('db_prefix') . '_spiel_text', 'aktion = \'Taktikaenderung\'');
		$messages = array();
		while ($message = $result->fetch_array()) {
			$messages[] = $message['id'];
		}
		$result->free();
		
		if (!count($messages)) {
			return;
		}
		
		$messageId = $messages[array_rand($messages)];
		
		$this->_db->queryInsert(array(
				'match_id' => $matchId,
				'message_id' => $messageId,
				'minute' => $minute,
				'active_home' => $isHomeTeam,
				'playernames' => $user->username
				), 
				$this->_websoccer->getConfig('db_prefix') . '_matchreport');
	}
	
}

?>