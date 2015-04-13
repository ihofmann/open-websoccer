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
 * Provides data for the match formation form.
 */
class FormationModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_nationalteam;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_nationalteam = ($websoccer->getRequestParameter('nationalteam')) ? TRUE : FALSE;
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
		
		// get team players
		if ($this->_nationalteam) {
			$clubId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		} else {
			$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		}
		
		// next x matches
		$nextMatches = MatchesDataService::getNextMatches($this->_websoccer, $this->_db, $clubId, 
				$this->_websoccer->getConfig('formation_max_next_matches'));
		if (!count($nextMatches)) {
			throw new Exception($this->_i18n->getMessage('next_match_block_no_nextmatch'));
		}
		
		// currently selected match
		$matchId = $this->_websoccer->getRequestParameter('id');
		if (!$matchId) {
			$matchinfo = $nextMatches[0];
		} else {
			foreach ($nextMatches as $nextMatch) {
				if ($nextMatch['match_id'] == $matchId) {
					$matchinfo = $nextMatch;
					break;
				}
			}
		}
		if (!isset($matchinfo)) {
			throw new Exception('illegal match id');
		}
		
		$players = null;
		if ($clubId > 0) {
			if ($this->_nationalteam) {
				$players = NationalteamsDataService::getNationalPlayersOfTeamByPosition($this->_websoccer, $this->_db, $clubId);
			} else {
				$players = PlayersDataService::getPlayersOfTeamByPosition($this->_websoccer, $this->_db, $clubId, 'DESC', count($matchinfo) && $matchinfo['match_type'] == 'cup',
						(isset($matchinfo['match_type']) && $matchinfo['match_type'] != 'friendly'));
			}
		}
		
		// load template
		if ($this->_websoccer->getRequestParameter('templateid')) {
			$formation = FormationDataService::getFormationByTemplateId($this->_websoccer, $this->_db, $clubId, $this->_websoccer->getRequestParameter('templateid'));
		} else {
			// get previously saved formation and tactic
			$formation = FormationDataService::getFormationByTeamId($this->_websoccer, $this->_db, $clubId, $matchinfo['match_id']);
		}
		
		for ($benchNo = 1; $benchNo <= 5; $benchNo++) {
			if ($this->_websoccer->getRequestParameter('bench' . $benchNo)) {
				$formation['bench' . $benchNo] = $this->_websoccer->getRequestParameter('bench' . $benchNo);
			} else if (!isset($formation['bench' . $benchNo])) {
				$formation['bench' . $benchNo] = '';
			}
		}
		
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if ($this->_websoccer->getRequestParameter('sub' . $subNo .'_out')) {
				$formation['sub' . $subNo .'_out'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_out');
				$formation['sub' . $subNo .'_in'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_in');
				$formation['sub' . $subNo .'_minute'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_minute');
				$formation['sub' . $subNo .'_condition'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_condition');
				$formation['sub' . $subNo .'_position'] = $this->_websoccer->getRequestParameter('sub' . $subNo .'_position');
			} else if (!isset($formation['sub' . $subNo .'_out'])) {
				$formation['sub' . $subNo .'_out'] = '';
				$formation['sub' . $subNo .'_in'] = '';
				$formation['sub' . $subNo .'_minute'] = '';
				$formation['sub' . $subNo .'_condition'] = '';
				$formation['sub' . $subNo .'_position'] = '';
			}
		}
		
		$setup = $this->getFormationSetup($formation);
		
		// select players from team by criteria
		$criteria = $this->_websoccer->getRequestParameter('preselect');
		if ($criteria !== NULL) {
			
			if ($criteria == 'strongest') {
				$sortColumn = 'w_staerke';
			} elseif ($criteria == 'freshest') {
				$sortColumn = 'w_frische';
			} else {
				$sortColumn = 'w_zufriedenheit';
			}

			$proposedPlayers = FormationDataService::getFormationProposalForTeamId($this->_websoccer, $this->_db, $clubId, 
					$setup['defense'], $setup['dm'], $setup['midfield'], $setup['om'], $setup['striker'], $setup['outsideforward'], $sortColumn, 'DESC', 
					$this->_nationalteam, (isset($matchinfo['match_type']) && $matchinfo['match_type'] == 'cup'));
			
			for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
				$playerIndex = $playerNo - 1;
				if (isset($proposedPlayers[$playerIndex])) {
					$formation['player' . $playerNo] = $proposedPlayers[$playerIndex]['id'];
					$formation['player' . $playerNo . '_pos'] = $proposedPlayers[$playerIndex]['position'];
				}
			}
			
			// clear bench (prevents duplicate players)
			for ($benchNo = 1; $benchNo <= 5; $benchNo++) {
				$formation['bench' . $benchNo] = '';
			}
			for ($subNo = 1; $subNo <= 3; $subNo++) {
				$formation['sub' . $subNo .'_out'] = '';
				$formation['sub' . $subNo .'_in'] = '';
				$formation['sub' . $subNo .'_minute'] = '';
				$formation['sub' . $subNo .'_condition'] = '';
				$formation['sub' . $subNo .'_position'] = '';
			}
		}
		
		// free kick taker
		if ($this->_websoccer->getRequestParameter('freekickplayer')) {
			$formation['freekickplayer'] = $this->_websoccer->getRequestParameter('freekickplayer');
		} else if (!isset($formation['freekickplayer'])) {
			$formation['freekickplayer'] = '';
		}
		
		// tactical options
		if ($this->_websoccer->getRequestParameter('offensive')) {
			$formation['offensive'] = $this->_websoccer->getRequestParameter('offensive');
		} else if (!isset($formation['offensive'])) {
			$formation['offensive'] = 40;
		}
		
		if ($this->_websoccer->getRequestParameter('longpasses')) {
			$formation['longpasses'] = $this->_websoccer->getRequestParameter('longpasses');
		}
		if ($this->_websoccer->getRequestParameter('counterattacks')) {
			$formation['counterattacks'] = $this->_websoccer->getRequestParameter('counterattacks');
		}
		
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			// set player from request
			if ($this->_websoccer->getRequestParameter('player' . $playerNo)) {
				$formation['player' . $playerNo] = $this->_websoccer->getRequestParameter('player' . $playerNo);
				$formation['player' . $playerNo . '_pos'] = $this->_websoccer->getRequestParameter('player' . $playerNo . '_pos');
				
				// set to 0 if no previous formation is available
			} else if (!isset($formation['player' . $playerNo])) {
				$formation['player' . $playerNo] = '';
				$formation['player' . $playerNo . '_pos'] = '';
			}
		}
		
		return array('nextMatches' => $nextMatches,
				'next_match' => $matchinfo, 
				'previous_matches' => MatchesDataService::getPreviousMatches($matchinfo, $this->_websoccer, $this->_db),
				'players' => $players, 
				'formation' => $formation, 
				'setup' => $setup,
				'captain_id' => TeamsDataService::getTeamCaptainIdOfTeam($this->_websoccer, $this->_db, $clubId));
	}
	
	protected function getFormationSetup($formation) {
		
		// default setup
		$setup = array('defense' => 4,
						'dm' => 1,
						'midfield' => 3,
						'om' => 1,
						'striker' => 1,
						'outsideforward' => 0);
		
		// override by user input
		if ($this->_websoccer->getRequestParameter('formation_defense') !== NULL) {
			$setup['defense'] = (int) $this->_websoccer->getRequestParameter('formation_defense');
			$setup['dm'] = (int) $this->_websoccer->getRequestParameter('formation_defensemidfield');
			$setup['midfield'] = (int) $this->_websoccer->getRequestParameter('formation_midfield');
			$setup['om'] = (int) $this->_websoccer->getRequestParameter('formation_offensivemidfield');
			$setup['striker'] = (int) $this->_websoccer->getRequestParameter('formation_forward');
			$setup['outsideforward'] = (int) $this->_websoccer->getRequestParameter('formation_outsideforward');
			
			// override by request when page is re-loaded after submitting the main form
		} elseif ($this->_websoccer->getRequestParameter('setup') !== NULL) {
			
			$setupParts = explode('-', $this->_websoccer->getRequestParameter('setup'));
				
			$setup['defense'] = (int) $setupParts[0];
			$setup['dm'] = (int) $setupParts[1];
			$setup['midfield'] = (int) $setupParts[2];
			$setup['om'] = (int) $setupParts[3];
			$setup['striker'] = (int) $setupParts[4];
			$setup['outsideforward'] = (int) $setupParts[5];
			
			// override by previously saved formation
		} else if (isset($formation['setup']) && strlen($formation['setup'])) {
			$setupParts = explode('-', $formation['setup']);
			
			$setup['defense'] = (int) $setupParts[0];
			$setup['dm'] = (int) $setupParts[1];
			$setup['midfield'] = (int) $setupParts[2];
			$setup['om'] = (int) $setupParts[3];
			$setup['striker'] = (int) $setupParts[4];
			
			// check number of elements due to backwards compatibility
			if (count($setupParts) > 5) {
				$setup['outsideforward'] = (int) $setupParts[5];
			} else {
				$setup['outsideforward'] = 0;
			}
		}
		
		// alter setup if invalid
		$altered = FALSE;
		while (($noOfPlayers = $setup['defense'] + $setup['dm'] + $setup['midfield'] + $setup['om'] + $setup['striker'] + $setup['outsideforward']) != 10) {
			
			// reduce players
			if ($noOfPlayers > 10) {
				
				if ($setup['striker'] > 1) {
					$setup['striker'] = $setup['striker'] - 1;
				} elseif ($setup['outsideforward'] > 1) {
					$setup['outsideforward'] = 0;
				} elseif ($setup['om'] > 1) {
					$setup['om'] = $setup['om'] - 1;
				} elseif ($setup['dm'] > 1) {
					$setup['dm'] = $setup['dm'] - 1;
				} elseif ($setup['midfield'] > 2) {
					$setup['midfield'] = $setup['midfield'] - 1;
				} else {
					$setup['defense'] = $setup['defense'] - 1;
				}
				
				// increase
			} else {
				
				if ($setup['defense'] < 4) {
					$setup['defense'] = $setup['defense'] + 1;
				} else if ($setup['midfield'] < 4) {
					$setup['midfield'] = $setup['midfield'] + 1;
				} else if ($setup['dm'] < 2) {
					$setup['dm'] = $setup['dm'] + 1;
				} else if ($setup['om'] < 2) {
					$setup['om'] = $setup['om'] + 1;
				} else {
					$setup['striker'] = $setup['striker'] + 1;
				}
			}
			
			$altered = TRUE;
		}
		
		if ($altered) {
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING, 
					$this->_i18n->getMessage('formation_setup_altered_warn_title'), 
					$this->_i18n->getMessage('formation_setup_altered_warn_details')));
		}
		
		return $setup;
	}
	
}

?>