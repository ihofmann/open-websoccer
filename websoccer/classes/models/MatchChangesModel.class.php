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
 * Provides data for changing tactics during a live match.
 */
class MatchChangesModel extends FormationModel {
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
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$matchId = (int) $this->_websoccer->getRequestParameter('id');
		if ($matchId < 1) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$match = MatchesDataService::getMatchSubstitutionsById($this->_websoccer, $this->_db, $matchId);
		
		if ($match['match_simulated']) {
			throw new Exception($this->_i18n->getMessage('match_details_match_completed'));
		}
		
		$teamId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		if ($match['match_home_id'] !== $teamId && $match['match_guest_id'] !== $teamId) {
			$teamId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		}
		
		if ($teamId !== $match['match_home_id']  && $match['match_guest_id'] !== $teamId) {
			throw new Exception('illegal match');
		}
		$teamPrefix = ($teamId == $match['match_home_id']) ? 'home' : 'guest';
		
		$players = MatchesDataService::getMatchPlayerRecordsByField($this->_websoccer, $this->_db, $matchId, $teamId);
		$playersOnField = $players['field'];
		$playersOnBench = (isset($players['bench'])) ? $players['bench'] : array();
		
		$formation = array();
		
		if ($this->_websoccer->getRequestParameter('freekickplayer')) {
			$formation['freekickplayer'] = $this->_websoccer->getRequestParameter('freekickplayer');
		} else {
			$formation['freekickplayer'] = $match['match_' . $teamPrefix . '_freekickplayer'];
		}
			
		if ($this->_websoccer->getRequestParameter('offensive')) {
			$formation['offensive'] = $this->_websoccer->getRequestParameter('offensive');
		} else {
			$formation['offensive'] = $match['match_' . $teamPrefix . '_offensive'];
		}
		
		if ($this->_websoccer->getRequestParameter('longpasses')) {
			$formation['longpasses'] = $this->_websoccer->getRequestParameter('longpasses');
		} else {
			$formation['longpasses'] = $match['match_' . $teamPrefix . '_longpasses'];
		}
		
		if ($this->_websoccer->getRequestParameter('counterattacks')) {
			$formation['counterattacks'] = $this->_websoccer->getRequestParameter('counterattacks');
		} else {
			$formation['counterattacks'] = $match['match_' . $teamPrefix . '_counterattacks'];
		}
		
		// get existing formation
		$playerNo = 0;
		foreach ($playersOnField as $player) {
			$playerNo++;
			$formation['player' . $playerNo] = $player['id'];
			$formation['player' . $playerNo . '_pos'] = $player['match_position_main'];
		}
		
		// set setup
		$setup = array('defense' => 6,
				'dm' => 3,
				'midfield' => 4,
				'om' => 3,
				'striker' => 2,
				'outsideforward' => 2);
		$setupMainMapping = array('LV' => 'defense',
				'RV' => 'defense',
				'IV' => 'defense',
				'DM' => 'dm',
				'LM' => 'midfield',
				'ZM' => 'midfield',
				'RM' => 'midfield',
				'OM' => 'om',
				'LS' => 'outsideforward',
				'MS' => 'striker',
				'RS' => 'outsideforward');
		$setupPosMapping = array('Abwehr' => 'defense',
				'Mittelfeld' => 'midfield',
				'Sturm' => 'striker');
		
		// override formation by user input and count setup
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			if ($this->_websoccer->getRequestParameter('player' . $playerNo) > 0) {
				$formation['player' . $playerNo] = $this->_websoccer->getRequestParameter('player' . $playerNo);
				$formation['player' . $playerNo . '_pos'] = $this->_websoccer->getRequestParameter('player' . $playerNo . '_pos');
			}
		}
		
		// bench
		$benchNo = 0;
		foreach ($playersOnBench as $player) {
			$benchNo++;
			$formation['bench' . $benchNo] = $player['id'];
		}
		for ($benchNo = 1; $benchNo <= 5; $benchNo++) {
			if ($this->_websoccer->getRequestParameter('bench' . $benchNo)) {
				$formation['bench' . $benchNo] = $this->_websoccer->getRequestParameter('bench' . $benchNo);
			} else if (!isset($formation['bench' . $benchNo])) {
				$formation['bench' . $benchNo] = '';
			}
		}
		
		// subs
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if ($this->_websoccer->getRequestParameter('sub' . $subNo .'_out')) {
				$formation['sub' . $subNo .'_out'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_out');
				$formation['sub' . $subNo .'_in'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_in');
				$formation['sub' . $subNo .'_minute'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_minute');
				$formation['sub' . $subNo .'_condition'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_condition');
				$formation['sub' . $subNo .'_position'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_position');
			} else if (isset($match[$teamPrefix . '_sub' . $subNo .'_out'])) {
				$formation['sub' . $subNo .'_out'] = $match[$teamPrefix . '_sub' . $subNo .'_out'];
				$formation['sub' . $subNo .'_in'] = $match[$teamPrefix . '_sub' . $subNo .'_in'];
				$formation['sub' . $subNo .'_minute'] = $match[$teamPrefix . '_sub' . $subNo .'_minute'];
				$formation['sub' . $subNo .'_condition'] = $match[$teamPrefix . '_sub' . $subNo .'_condition'];
				$formation['sub' . $subNo .'_position'] = $match[$teamPrefix . '_sub' . $subNo .'_position'];
			} else {
				$formation['sub' . $subNo .'_out'] = '';
				$formation['sub' . $subNo .'_in'] = '';
				$formation['sub' . $subNo .'_minute'] = '';
				$formation['sub' . $subNo .'_condition'] = '';
				$formation['sub' . $subNo .'_position'] = '';
			}
		}
		
		return array('setup' => $setup, 'players' => $players, 'formation' => $formation, 'minute' => $match['match_minutes']);
	}
	
}

?>