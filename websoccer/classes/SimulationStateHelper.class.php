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
 * Helps creating and updating the simulation state. 
 * This enables live simulation (storing and loading the simulation state of a specified match).
 * 
 * @author Ingo Hofmann
 */
class SimulationStateHelper {
	
	private static $_addedPlayers; // players cache with key=ID, value=player instance
	
	/**
	 * Creates a new record within the simulation state DB table for the specified player.
	 * The state table enables live-simulation (simulating not the whole match a once, but only parts) and also stores all
	 * statistical information about the player for the specified match id (e.g. ball contacts, cards, attempts, etc.).
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db Database connection.
	 * @param int $matchId match ID
	 * @param SimulationPlayer $player player model to store.
	 * @param boolean $onBench TRUE if player is on bench, FALSE if on pitch.
	 */
	public static function createSimulationRecord(WebSoccer $websoccer, DbConnection $db, $matchId, SimulationPlayer $player, $onBench = FALSE) {
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel_berechnung';
		
		$db->queryInsert(self::getPlayerColumns($matchId, $player, ($onBench) ? 'Ersatzbank' : '1'), $fromTable);
	}
	
	/**
	 * Saves the current match state (statistics, players, results) in database.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db Database connection.
	 * @param SimulationMatch $match Match state to store.
	 */
	public static function updateState(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match) {
		self::updateMatch($websoccer, $db, $match);
		self::updateTeamState($websoccer, $db, $match, $match->homeTeam);
		self::updateTeamState($websoccer, $db, $match, $match->guestTeam);
	}
	
	/**
	 * Loads the simulation state (statistics, players, results, etc.) from the database and builds the internal model
	 * for continuing the match simulation.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db database connection.
	 * @param array $matchinfo match data from match database table.
	 * @return SimulationMatch loaded match, ready for simulation.
	 */
	public static function loadMatchState(WebSoccer $websoccer, DbConnection $db, $matchinfo) {
		
		$homeTeam = new SimulationTeam($matchinfo['home_id']);
		$guestTeam = new SimulationTeam($matchinfo['guest_id']);
		
		self::loadTeam($websoccer, $db, $matchinfo['match_id'], $homeTeam);
		self::loadTeam($websoccer, $db, $matchinfo['match_id'], $guestTeam);
		
		$homeTeam->setGoals($matchinfo['home_goals']);
		$homeTeam->offensive = $matchinfo['home_offensive'];
		$homeTeam->isNationalTeam = $matchinfo['home_nationalteam'];
		$homeTeam->isManagedByInterimManager = $matchinfo['home_interimmanager'];
		$homeTeam->noFormationSet = $matchinfo['home_noformation'];
		$homeTeam->setup = $matchinfo['home_setup'];
		$homeTeam->name = $matchinfo['home_name'];
		$homeTeam->longPasses = $matchinfo['home_longpasses'];
		$homeTeam->counterattacks = $matchinfo['home_counterattacks'];
		$homeTeam->morale = $matchinfo['home_morale'];
		
		$guestTeam->setGoals($matchinfo['guest_goals']);
		$guestTeam->offensive = $matchinfo['guest_offensive'];
		$guestTeam->isNationalTeam = $matchinfo['guest_nationalteam'];
		$guestTeam->isManagedByInterimManager = $matchinfo['guest_interimmanager'];
		$guestTeam->noFormationSet = $matchinfo['guest_noformation'];
		$guestTeam->setup = $matchinfo['guest_setup'];
		$guestTeam->name = $matchinfo['guest_name'];
		$guestTeam->longPasses = $matchinfo['guest_longpasses'];
		$guestTeam->counterattacks = $matchinfo['guest_counterattacks'];
		$guestTeam->morale = $matchinfo['guest_morale'];
		
		$match = new SimulationMatch($matchinfo['match_id'], $homeTeam, $guestTeam, $matchinfo['minutes']);
		$match->type = $matchinfo['type'];
		$match->penaltyShootingEnabled = $matchinfo['penaltyshooting'];
		$match->isSoldOut = $matchinfo['soldout'];
		$match->cupName = $matchinfo['cup_name'];
		$match->cupRoundName = $matchinfo['cup_roundname'];
		$match->cupRoundGroup = $matchinfo['cup_groupname'];
		$match->isAtForeignStadium = ($matchinfo['custom_stadium_id']) ? TRUE : FALSE;
		
		//get and set player with ball
		if ($matchinfo['player_with_ball'] && isset(self::$_addedPlayers[$matchinfo['player_with_ball']])) {
			$match->setPlayerWithBall(self::$_addedPlayers[$matchinfo['player_with_ball']]);
		}
		
		if ($matchinfo['prev_player_with_ball'] && isset(self::$_addedPlayers[$matchinfo['prev_player_with_ball']])) {
			$match->setPreviousPlayerWithBall(self::$_addedPlayers[$matchinfo['prev_player_with_ball']]);
		}
		
		// set free kick takers
		if ($matchinfo['home_freekickplayer'] && isset(self::$_addedPlayers[$matchinfo['home_freekickplayer']])) {
			$homeTeam->freeKickPlayer = self::$_addedPlayers[$matchinfo['home_freekickplayer']];
		}
		if ($matchinfo['guest_freekickplayer'] && isset(self::$_addedPlayers[$matchinfo['guest_freekickplayer']])) {
			$guestTeam->freeKickPlayer = self::$_addedPlayers[$matchinfo['guest_freekickplayer']];
		}
		
		// substitutions
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if ($matchinfo['home_sub_' . $subNo . '_out'] 
					&& isset(self::$_addedPlayers[$matchinfo['home_sub_' . $subNo . '_in']])
					&& isset(self::$_addedPlayers[$matchinfo['home_sub_' . $subNo . '_out']])) {
				$sub = new SimulationSubstitution($matchinfo['home_sub_' . $subNo . '_minute'], 
						self::$_addedPlayers[$matchinfo['home_sub_' . $subNo . '_in']],
						self::$_addedPlayers[$matchinfo['home_sub_' . $subNo . '_out']],
						$matchinfo['home_sub_' . $subNo . '_condition'],
						$matchinfo['home_sub_' . $subNo . '_position']);
				$homeTeam->substitutions[] = $sub;
			}
			
			if ($matchinfo['guest_sub_' . $subNo . '_out']
					&& isset(self::$_addedPlayers[$matchinfo['guest_sub_' . $subNo . '_in']])
					&& isset(self::$_addedPlayers[$matchinfo['guest_sub_' . $subNo . '_out']])) {
				$sub = new SimulationSubstitution($matchinfo['guest_sub_' . $subNo . '_minute'],
						self::$_addedPlayers[$matchinfo['guest_sub_' . $subNo . '_in']],
						self::$_addedPlayers[$matchinfo['guest_sub_' . $subNo . '_out']],
						$matchinfo['guest_sub_' . $subNo . '_condition'],
						$matchinfo['guest_sub_' . $subNo . '_position']);
				$guestTeam->substitutions[] = $sub;
			}
		}
		
		// reset cache
		self::$_addedPlayers = null;
		
		return $match;
	}
	
	private static function updateMatch(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match) {
		
		if ($match->isCompleted) {
			$columns['berechnet'] = 1;
		}
		
		$columns['minutes'] = $match->minute;
		$columns['soldout'] = ($match->isSoldOut) ? '1' : '0';
		$columns['home_tore'] = $match->homeTeam->getGoals();
		$columns['gast_tore'] = $match->guestTeam->getGoals();
		
		$columns['home_setup'] = $match->homeTeam->setup;
		$columns['gast_setup'] = $match->guestTeam->setup;
		
		$columns['home_offensive'] = $match->homeTeam->offensive;
		$columns['gast_offensive'] = $match->guestTeam->offensive;
		
		$columns['home_noformation'] = ($match->homeTeam->noFormationSet) ? '1' : '0';
		$columns['guest_noformation'] = ($match->guestTeam->noFormationSet) ? '1' : '0';
		
		$columns['home_longpasses'] = ($match->homeTeam->longPasses) ? '1' : '0';
		$columns['gast_longpasses'] = ($match->guestTeam->longPasses) ? '1' : '0';
		
		$columns['home_counterattacks'] = ($match->homeTeam->counterattacks) ? '1' : '0';
		$columns['gast_counterattacks'] = ($match->guestTeam->counterattacks) ? '1' : '0';
		
		$columns['home_morale'] = $match->homeTeam->morale;
		$columns['gast_morale'] = $match->guestTeam->morale;
		
		if ($match->getPlayerWithBall() != null) {
			$columns['player_with_ball'] = $match->getPlayerWithBall()->id;
		} else {
			$columns['player_with_ball'] = 0;
		}
		
		if ($match->getPreviousPlayerWithBall() != null) {
			$columns['prev_player_with_ball'] = $match->getPreviousPlayerWithBall()->id;
		} else {
			$columns['prev_player_with_ball'] = 0;
		}
		
		$columns['home_freekickplayer'] = ($match->homeTeam->freeKickPlayer != NULL) ? $match->homeTeam->freeKickPlayer->id : '';
		$columns['gast_freekickplayer'] = ($match->guestTeam->freeKickPlayer != NULL) ? $match->guestTeam->freeKickPlayer->id : '';
		
		// substitutions
		if (is_array($match->homeTeam->substitutions)) {
			$subIndex = 1;
			foreach ($match->homeTeam->substitutions as $substitution) {
				$columns['home_w' . $subIndex . '_raus'] = $substitution->playerOut->id;
				$columns['home_w' . $subIndex . '_rein'] = $substitution->playerIn->id;
				$columns['home_w' . $subIndex . '_minute'] = $substitution->minute;
				$columns['home_w' . $subIndex . '_condition'] = $substitution->condition;
				$columns['home_w' . $subIndex . '_position'] = $substitution->position;
				
				$subIndex++;
			}
		}
		
		if (is_array($match->guestTeam->substitutions)) {
			$subIndex = 1;
			foreach ($match->guestTeam->substitutions as $substitution) {
				$columns['gast_w' . $subIndex . '_raus'] = $substitution->playerOut->id;
				$columns['gast_w' . $subIndex . '_rein'] = $substitution->playerIn->id;
				$columns['gast_w' . $subIndex . '_minute'] = $substitution->minute;
				$columns['gast_w' . $subIndex . '_condition'] = $substitution->condition;
				$columns['gast_w' . $subIndex . '_position'] = $substitution->position;
				
				$subIndex++;
			}
		}
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel';
		$whereCondition = 'id = %d';
		$parameters = $match->id;
		
		$db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
	private static function updateTeamState(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match, SimulationTeam $team) {
		
		// field players
		if (is_array($team->positionsAndPlayers)) {
			foreach ($team->positionsAndPlayers as $positions => $players) {
				foreach ($players as $player) {
					self::updatePlayerState($websoccer, $db, $match->id, $player, '1');
				}
			}
		}
		
		// bench
		if (is_array($team->playersOnBench)) {
			foreach ($team->playersOnBench as $player) {
				self::updatePlayerState($websoccer, $db, $match->id, $player, 'Ersatzbank');
			}
		}
		
		// removed
		if (is_array($team->removedPlayers)) {
			foreach ($team->removedPlayers as $player) {
				self::updatePlayerState($websoccer, $db, $match->id, $player, 'Ausgewechselt');
			}
		}
		
	}
	
	private static function updatePlayerState(WebSoccer $websoccer, DbConnection $db, $matchId, $player, $fieldArea) {
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel_berechnung';
		$whereCondition = 'spieler_id = %d AND spiel_id = %d';
		$parameters = array($player->id, $matchId);
		
		$columns = self::getPlayerColumns($matchId, $player, $fieldArea);
		
		$db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
	private static function getPlayerColumns($matchId, SimulationPlayer $player, $fieldArea) {
		$columns['spiel_id'] = $matchId;
		$columns['spieler_id'] = $player->id;
		$columns['team_id'] = $player->team->id;
		$columns['name'] = $player->name;
		$columns['note'] = $player->getMark();
		$columns['minuten_gespielt'] = $player->getMinutesPlayed();
		$columns['karte_gelb'] = $player->yellowCards;
		$columns['karte_rot'] = $player->redCard;
		$columns['verletzt'] = $player->injured;
		$columns['gesperrt'] = $player->blocked;
		$columns['tore'] = $player->getGoals();
		$columns['feld'] = $fieldArea;
		$columns['position'] = $player->position;
		$columns['position_main'] = $player->mainPosition;
		$columns['age'] = $player->age;
		
		$columns['w_staerke'] = $player->strength;
		$columns['w_technik'] = $player->strengthTech;
		$columns['w_kondition'] = $player->strengthStamina;
		$columns['w_frische'] = $player->strengthFreshness;
		$columns['w_zufriedenheit'] = $player->strengthSatisfaction;
		
		$columns['ballcontacts'] = $player->getBallContacts();
		$columns['wontackles'] = $player->getWonTackles();
		$columns['losttackles'] = $player->getLostTackles();
		$columns['shoots'] = $player->getShoots();
		$columns['passes_successed'] = $player->getPassesSuccessed();
		$columns['passes_failed'] = $player->getPassesFailed();
		$columns['assists'] = $player->getAssists();
		
		return $columns;
	}
	
	private static function loadTeam(WebSoccer $websoccer, DbConnection $db, $matchId, SimulationTeam $team) {
		
		// get players
		$columns['spieler_id'] = 'player_id';
		$columns['name'] = 'name';
		$columns['note'] = 'mark';
		$columns['minuten_gespielt'] = 'minutes_played';
		$columns['karte_gelb'] = 'yellow_cards';
		$columns['karte_rot'] = 'red_cards';
		$columns['verletzt'] = 'injured';
		$columns['gesperrt'] = 'blocked';
		$columns['tore'] = 'goals';
		$columns['feld'] = 'field_area';
		$columns['position'] = 'position';
		$columns['position_main'] = 'main_position';
		$columns['age'] = 'age';
		
		$columns['w_staerke'] = 'strength';
		$columns['w_technik'] = 'strength_tech';
		$columns['w_kondition'] = 'strength_stamina';
		$columns['w_frische'] = 'strength_freshness';
		$columns['w_zufriedenheit'] = 'strength_satisfaction';
		
		$columns['ballcontacts'] = 'ballcontacts';
		$columns['wontackles'] = 'wontackles';
		$columns['losttackles'] = 'losttackles';
		$columns['shoots'] = 'shoots';
		$columns['passes_successed'] = 'passes_successed';
		$columns['passes_failed'] = 'passes_failed';
		$columns['assists'] = 'assists';
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel_berechnung';
		$whereCondition = 'spiel_id = %d AND team_id = %d ORDER BY id ASC';
		$parameters = array($matchId, $team->id);
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		while ($playerinfo = $result->fetch_array()) {
			
			$player = new SimulationPlayer($playerinfo['player_id'], $team, $playerinfo['position'], $playerinfo['main_position'], 
					$playerinfo['mark'], $playerinfo['age'], $playerinfo['strength'], $playerinfo['strength_tech'], 
					$playerinfo['strength_stamina'], $playerinfo['strength_freshness'], $playerinfo['strength_satisfaction']);
			
			$player->name = $playerinfo['name'];
			$player->setBallContacts($playerinfo['ballcontacts']);
			$player->setWonTackles($playerinfo['wontackles']);
			$player->setLostTackles($playerinfo['losttackles']);
			$player->setGoals($playerinfo['goals']);
			$player->setShoots($playerinfo['shoots']);
			$player->setPassesSuccessed($playerinfo['passes_successed']);
			$player->setPassesFailed($playerinfo['passes_failed']);
			$player->setAssists($playerinfo['assists']);
			$player->setMinutesPlayed($playerinfo['minutes_played'], FALSE);
			
			$player->yellowCards = $playerinfo['yellow_cards'];
			$player->redCard = $playerinfo['red_cards'];
			$player->injured = $playerinfo['injured'];
			$player->blocked = $playerinfo['blocked'];
			
			// add player
			self::$_addedPlayers[$player->id] = $player;
			
			if ($playerinfo['field_area'] == 'Ausgewechselt') {
				$team->removedPlayers[$player->id] = $player;
			} else if ($playerinfo['field_area'] == 'Ersatzbank') {
				$team->playersOnBench[$player->id] = $player;
			} else {
				$team->positionsAndPlayers[$player->position][] = $player;
			}
		}
		$result->free();
		
	}
}

?>
