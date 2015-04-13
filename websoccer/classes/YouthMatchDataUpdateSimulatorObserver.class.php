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
 * Updates data base after a youth match has completed.
 * 
 * @author Ingo Hofmann
 */
class YouthMatchDataUpdateSimulatorObserver implements ISimulatorObserver {
	private $_websoccer;
	private $_db;
	
	/**
	 * 
	 * @param WebSoccer $websoccer application context
	 * @param DbConnection $db DB connection
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db) {
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * @see ISimulatorObserver::onBeforeMatchStarts()
	 */
	public function onBeforeMatchStarts(SimulationMatch $match) {
		// nothing to do here, just be compliant with API...
	}
	
	/**
	 * Create a match report item.
	 * 
	 * @see ISimulatorObserver::onSubstitution()
	 */
	public function onSubstitution(SimulationMatch $match, SimulationSubstitution $substitution) {
		YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
			'ymreport_substitution', array(
				'in' => $substitution->playerIn->name, 
				'out' => $substitution->playerOut->name), $substitution->playerIn->team->id == $match->homeTeam->id);
	}
	
	/**
	 * @see ISimulatorObserver::onMatchCompleted()
	 */
	public function onMatchCompleted(SimulationMatch $match) {
	
		$this->_updateTeam($match, $match->homeTeam);
		$this->_updateTeam($match, $match->guestTeam);
		
		// save result and set as simulated
		$columns = array(
				'home_noformation' => ($match->homeTeam->noFormationSet) ? '1' : '0',
				'home_goals' => $match->homeTeam->getGoals(),
				'guest_noformation' => ($match->guestTeam->noFormationSet) ? '1' : '0',
				'guest_goals' => $match->guestTeam->getGoals(),
				'simulated' => '1'
				);
		$this->_db->queryUpdate($columns, $this->_websoccer->getConfig('db_prefix') . '_youthmatch', 'id = %d', $match->id);
	}
	
	/**
	 * pay salary ans triger player updates.
	 * 
	 * @param SimulationMatch $match
	 * @param SimulationTeam $team
	 */
	private function _updateTeam(SimulationMatch $match, SimulationTeam $team) {
		// debit players salary
		$salary = YouthPlayersDataService::computeSalarySumOfYouthPlayersOfTeam($this->_websoccer, $this->_db, $team->id);
		if ($salary) {
			BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $team->id, 
				$salary, 'youthteam_salarypayment_subject', 'match_salarypayment_sender');
		}
		
		// update players who played
		if (is_array($team->positionsAndPlayers)) {
			foreach($team->positionsAndPlayers as $position => $players) {
				foreach ($players as $player) {
					$this->_updatePlayer($match, $player, TRUE);
				}
			}
		}
		if (is_array($team->removedPlayers)) {
			foreach ($team->removedPlayers as $player) {
				$this->_updatePlayer($match, $player, FALSE);
			}
		}
	}
	
	/**
	 * Update overall player statistics and match records of player.
	 * 
	 * @param SimulationMatch $match
	 * @param SimulationPlayer $player
	 * @param $isOnPitch TRUE if player is on pitch in the end, FALSE if got removed (substitution or red card or injury).
	 */
	private function _updatePlayer(SimulationMatch $match, SimulationPlayer $player, $isOnPitch) {
		
		// update match statistics
		$columns = array(
				'name' => $player->name,
				'position_main' => $player->mainPosition,
				'grade' => $player->getMark(),
				'minutes_played' => $player->getMinutesPlayed(),
				'card_yellow' => $player->yellowCards,
				'card_red' => $player->redCard,
				'goals' => $player->getGoals(),
				'strength' => $player->strength,
				'ballcontacts' => $player->getBallContacts(),
				'wontackles' => $player->getWonTackles(),
				'shoots' => $player->getShoots(),
				'passes_successed' => $player->getPassesSuccessed(),
				'passes_failed' => $player->getPassesFailed(),
				'assists' => $player->getAssists(),
				'state' => ($isOnPitch) ? '1' : 'Ausgewechselt'
				);
		$this->_db->queryUpdate($columns, $this->_websoccer->getConfig('db_prefix') . '_youthmatch_player', 
				'match_id = %d AND player_id = %d', array($match->id, $player->id));
		
		// update player record, if actually played
		if ($this->_websoccer->getConfig('sim_played_min_minutes') <= $player->getMinutesPlayed()) {
			
			// query existing statistics
			$result = $this->_db->querySelect('*', $this->_websoccer->getConfig('db_prefix') . '_youthplayer', 
					'id = %d', $player->id);
			$playerinfo = $result->fetch_array();
			$result->free();
			
			$strengthChange = $this->_computeStrengthChange($player);
			
			// trigger plug-ins
			$event = new YouthPlayerPlayedEvent($this->_websoccer, $this->_db, I18n::getInstance($this->_websoccer->getConfig('supported_languages')), 
					$player, $strengthChange);
			PluginMediator::dispatchEvent($event);
			
			$yellowRedCards = 0;
			if ($player->yellowCards == 2) {
				$yellowCards = 1;
				$yellowRedCards = 1;
			} else {
				$yellowCards = $player->yellowCards;
			}
			
			// ensure that new strength does not exceed boundaries (max/min strength)
			$strength = $playerinfo['strength'] + $strengthChange;
			$maxStrength = $this->_websoccer->getConfig('youth_scouting_max_strength');
			$minStrength = $this->_websoccer->getConfig('youth_scouting_min_strength');
			if ($strength > $maxStrength) {
				$strengthChange = 0;
				$strength = $maxStrength;
			} else if ($strength < $minStrength) {
				$strengthChange = 0;
				$strength = $minStrength;
			}
			
			// save
			$columns = array(
					'strength' => $strength,
					'strength_last_change' => $strengthChange,
					'st_goals' => $playerinfo['st_goals'] + $player->getGoals(),
					'st_matches' => $playerinfo['st_matches'] + 1,
					'st_assists' => $playerinfo['st_assists'] + $player->getAssists(),
					'st_cards_yellow' => $playerinfo['st_cards_yellow'] + $yellowCards,
					'st_cards_yellow_red' => $playerinfo['st_cards_yellow_red'] + $yellowRedCards,
					'st_cards_red' => $playerinfo['st_cards_red'] + $player->redCard
					);
			$this->_db->queryUpdate($columns, $this->_websoccer->getConfig('db_prefix') . '_youthplayer',
					'id = %d', $player->id);
		}
	}
	
	/**
	 * Computes strength change after match, depending on player's grade/mark.
	 * 
	 * @param SimulationPlayer $player
	 * @return int number of strength points which the player gained.
	 */
	private function _computeStrengthChange(SimulationPlayer $player) {
		$mark = $player->getMark();
		
		if ($mark <= 1.3) {
			return $this->_websoccer->getConfig('youth_strengthchange_verygood');
		} else if ($mark <= 2.3) {
			return $this->_websoccer->getConfig('youth_strengthchange_good');
		} else if ($mark > 4.25 && $mark <= 5) {
			return $this->_websoccer->getConfig('youth_strengthchange_bad');
		} else if ($mark > 5) {
			return $this->_websoccer->getConfig('youth_strengthchange_verybad');
		}
		
		return 0;
	}
	
}
?>