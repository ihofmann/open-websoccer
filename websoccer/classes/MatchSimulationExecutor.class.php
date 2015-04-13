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
 * Finds matches to simulate, initializes them and triggers their simulation.
 * 
 * @author Ingo Hofmann
 */
class MatchSimulationExecutor {
	
	/**
	 * Gets matches to simulate, registers simulation obervers, creates internal data model (loading data from the database)
	 * and eventually calls the Simulator. After simulation, it stores the current state.
	 * Additionally, it triggers also youth matches simulation, in case maximum number of matches has not exceeded yet.
	 * 
	 * @param WebSoccer $websoccer request context.
	 * @param DbConnection $db database connection.
	 */
	public static function simulateOpenMatches(WebSoccer $websoccer, DbConnection $db) {
		
		$simulator = new Simulator($db, $websoccer);
		
		// add creating match report texts
		$strategy = $simulator->getSimulationStrategy();
		$simulationObservers = explode(',', $websoccer->getConfig('sim_simulation_observers'));
		foreach ($simulationObservers as $observerClassName) {
			$observerClass = trim($observerClassName);
			if (strlen($observerClass)) {
				$strategy->attachObserver(new $observerClass($websoccer, $db));
			}
		}
		
		$simulatorObservers = explode(',', $websoccer->getConfig('sim_simulator_observers'));
		foreach ($simulatorObservers as $observerClassName) {
			$observerClass = trim($observerClassName);
			if (strlen($observerClass)) {
				$simulator->attachObserver(new $observerClass($websoccer, $db));
			}
		}
		
		// find and execute open matches
		$fromTable = $websoccer->getConfig('db_prefix') .'_spiel AS M';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') .'_verein AS HOME_T ON HOME_T.id = M.home_verein';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') .'_verein AS GUEST_T ON GUEST_T.id = M.gast_verein';
		$fromTable .= ' LEFT JOIN ' . $websoccer->getConfig('db_prefix') .'_aufstellung AS HOME_F ON HOME_F.match_id = M.id AND HOME_F.verein_id = M.home_verein';
		$fromTable .= ' LEFT JOIN ' . $websoccer->getConfig('db_prefix') .'_aufstellung AS GUEST_F ON GUEST_F.match_id = M.id AND GUEST_F.verein_id = M.gast_verein';
		
		$columns['M.id'] = 'match_id';
		$columns['M.spieltyp'] = 'type';
		$columns['M.home_verein'] = 'home_id';
		$columns['M.gast_verein'] = 'guest_id';
		$columns['M.minutes'] = 'minutes';
		$columns['M.soldout'] = 'soldout';
		$columns['M.elfmeter'] = 'penaltyshooting';
		$columns['M.pokalname'] = 'cup_name';
		$columns['M.pokalrunde'] = 'cup_roundname';
		$columns['M.pokalgruppe'] = 'cup_groupname';
		$columns['M.stadion_id'] = 'custom_stadium_id';
		
		$columns['M.player_with_ball'] = 'player_with_ball';
		$columns['M.prev_player_with_ball'] = 'prev_player_with_ball';
		$columns['M.home_tore'] = 'home_goals';
		$columns['M.gast_tore'] = 'guest_goals';
		
		$columns['M.home_offensive'] = 'home_offensive';
		$columns['M.home_setup'] = 'home_setup';
		$columns['M.home_noformation'] = 'home_noformation';
		$columns['M.home_longpasses'] = 'home_longpasses';
		$columns['M.home_counterattacks'] = 'home_counterattacks';
		$columns['M.home_morale'] = 'home_morale';
		$columns['M.home_freekickplayer'] = 'home_freekickplayer';
		$columns['M.gast_offensive'] = 'guest_offensive';
		$columns['M.guest_noformation'] = 'guest_noformation';
		$columns['M.gast_setup'] = 'guest_setup';
		$columns['M.gast_longpasses'] = 'guest_longpasses';
		$columns['M.gast_counterattacks'] = 'guest_counterattacks';
		$columns['M.gast_morale'] = 'guest_morale';
		$columns['M.gast_freekickplayer'] = 'guest_freekickplayer';
		
		$columns['HOME_F.id'] = 'home_formation_id';
		$columns['HOME_F.offensive'] = 'home_formation_offensive';
		$columns['HOME_F.setup'] = 'home_formation_setup';
		$columns['HOME_F.longpasses'] = 'home_formation_longpasses';
		$columns['HOME_F.counterattacks'] = 'home_formation_counterattacks';
		$columns['HOME_F.freekickplayer'] = 'home_formation_freekickplayer';
		
		$columns['HOME_T.name'] = 'home_name';
		$columns['HOME_T.nationalteam'] = 'home_nationalteam';
		$columns['HOME_T.interimmanager'] = 'home_interimmanager';
		$columns['HOME_T.captain_id'] = 'home_captain_id';
		$columns['GUEST_T.nationalteam'] = 'guest_nationalteam';
		$columns['GUEST_T.name'] = 'guest_name';
		$columns['GUEST_T.captain_id'] = 'guest_captain_id';
		$columns['GUEST_T.interimmanager'] = 'guest_interimmanager';
		
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			$columns['HOME_F.spieler' . $playerNo] = 'home_formation_player' . $playerNo;
			$columns['HOME_F.spieler' . $playerNo . '_position'] = 'home_formation_player_pos_' . $playerNo;
			$columns['GUEST_F.spieler' . $playerNo] = 'guest_formation_player' . $playerNo;
			$columns['GUEST_F.spieler' . $playerNo . '_position'] = 'guest_formation_player_pos_' . $playerNo;
			
			if ($playerNo <= 5) {
				$columns['HOME_F.ersatz' . $playerNo] = 'home_formation_bench' . $playerNo;
				$columns['GUEST_F.ersatz' . $playerNo] = 'guest_formation_bench' . $playerNo;
			}
		}
		
		// substitutions
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			// will be used for initial creation
			$columns['HOME_F.w' . $subNo . '_raus'] = 'home_formation_sub' . $subNo . '_out';
			$columns['HOME_F.w' . $subNo . '_rein'] = 'home_formation_sub' . $subNo . '_in';
			$columns['HOME_F.w' . $subNo . '_minute'] = 'home_formation_sub' . $subNo . '_minute';
			$columns['HOME_F.w' . $subNo . '_condition'] = 'home_formation_sub' . $subNo . '_condition';
			$columns['HOME_F.w' . $subNo . '_position'] = 'home_formation_sub' . $subNo . '_position';
			
			// will be used for loading from state
			$columns['M.home_w' . $subNo . '_raus'] = 'home_sub_' . $subNo . '_out';
			$columns['M.home_w' . $subNo . '_rein'] = 'home_sub_' . $subNo . '_in';
			$columns['M.home_w' . $subNo . '_minute'] = 'home_sub_' . $subNo . '_minute';
			$columns['M.home_w' . $subNo . '_condition'] = 'home_sub_' . $subNo . '_condition';
			$columns['M.home_w' . $subNo . '_position'] = 'home_sub_' . $subNo . '_position';
			
			$columns['GUEST_F.w' . $subNo . '_raus'] = 'guest_formation_sub' . $subNo . '_out';
			$columns['GUEST_F.w' . $subNo . '_rein'] = 'guest_formation_sub' . $subNo . '_in';
			$columns['GUEST_F.w' . $subNo . '_minute'] = 'guest_formation_sub' . $subNo . '_minute';
			$columns['GUEST_F.w' . $subNo . '_condition'] = 'guest_formation_sub' . $subNo . '_condition';
			$columns['GUEST_F.w' . $subNo . '_position'] = 'guest_formation_sub' . $subNo . '_position';
			
			$columns['M.gast_w' . $subNo . '_raus'] = 'guest_sub_' . $subNo . '_out';
			$columns['M.gast_w' . $subNo . '_rein'] = 'guest_sub_' . $subNo . '_in';
			$columns['M.gast_w' . $subNo . '_minute'] = 'guest_sub_' . $subNo . '_minute';
			$columns['M.gast_w' . $subNo . '_condition'] = 'guest_sub_' . $subNo . '_condition';
			$columns['M.gast_w' . $subNo . '_position'] = 'guest_sub_' . $subNo . '_position';
		}
		
		$columns['GUEST_F.id'] = 'guest_formation_id';
		$columns['GUEST_F.offensive'] = 'guest_formation_offensive';
		$columns['GUEST_F.setup'] = 'guest_formation_setup';
		$columns['GUEST_F.longpasses'] = 'guest_formation_longpasses';
		$columns['GUEST_F.counterattacks'] = 'guest_formation_counterattacks';
		$columns['GUEST_F.freekickplayer'] = 'guest_formation_freekickplayer';
		
		$whereCondition = 'M.berechnet != \'1\' AND M.blocked != \'1\' AND M.datum <= %d ORDER BY M.datum ASC';
		$parameters = $websoccer->getNowAsTimestamp();
		
		$interval = (int) $websoccer->getConfig('sim_interval');
		$maxMatches = (int) $websoccer->getConfig('sim_max_matches_per_run');
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $maxMatches);
		$matchesSimulated = 0;
		$lockArray = array('blocked' => '1');
		$unlockArray = array('blocked' => '0');
		$matchTable = $websoccer->getConfig('db_prefix') . '_spiel';
		while ($matchinfo = $result->fetch_array()) {
			
			// lock record
			$db->queryUpdate($lockArray, $matchTable, 'id = %d', $matchinfo['match_id']);
			
			$match = null;
			if ($matchinfo['minutes'] < 1) {
				$match = self::createInitialMatchData($websoccer, $db, $matchinfo);
			} else {
				$match = SimulationStateHelper::loadMatchState($websoccer, $db, $matchinfo);
			}
			
			if ($match != null) {
				$simulator->simulateMatch($match, $interval);
					
				SimulationStateHelper::updateState($websoccer, $db, $match);
			}
			
			// let garbage collector free memory before script execution by removing all references to objects
			$match->cleanReferences();
			unset($match);
			
			// unlock record
			$db->queryUpdate($unlockArray, $matchTable, 'id = %d', $matchinfo['match_id']);
			
			$matchesSimulated++;

		}
		$result->free();
		
		// Simulate youth matches
		$maxYouthMatchesToSimulate = $maxMatches - $matchesSimulated;
		if ($maxYouthMatchesToSimulate) {
			YouthMatchSimulationExecutor::simulateOpenYouthMatches($websoccer, $db, $maxYouthMatchesToSimulate);
		}
	}
	
	private static function handleBothTeamsHaveNoFormation(WebSoccer $websoccer, DbConnection $db, $homeTeam, $guestTeam, SimulationMatch $match) {
		$homeTeam->noFormationSet = TRUE;
		$guestTeam->noFormationSet = TRUE;
		
		if ($websoccer->getConfig('sim_noformation_bothteams') == 'computer') {
			SimulationFormationHelper::generateNewFormationForTeam($websoccer, $db, $homeTeam, $match->id);
			SimulationFormationHelper::generateNewFormationForTeam($websoccer, $db, $guestTeam, $match->id);
		} else {
			$match->isCompleted = TRUE;
		}
		
	}
	
	private static function handleOneTeamHasNoFormation(WebSoccer $websoccer, DbConnection $db, $team, SimulationMatch $match) {
		$team->noFormationSet = TRUE;
		
		if ($websoccer->getConfig('sim_createformation_without_manager') && self::teamHasNoManager($websoccer, $db, $team->id)) {
			SimulationFormationHelper::generateNewFormationForTeam($websoccer, $db, $team, $match->id);
		} else {
			if ($websoccer->getConfig('sim_noformation_oneteam') == '0_0') {
				$match->isCompleted = TRUE;
			} else if ($websoccer->getConfig('sim_noformation_oneteam') == '3_0') {
				$opponentTeam = ($match->homeTeam->id == $team->id) ? $match->guestTeam : $match->homeTeam;
				$opponentTeam->setGoals(3);
				$match->isCompleted = TRUE;
			} else {
				SimulationFormationHelper::generateNewFormationForTeam($websoccer, $db, $team, $match->id);
			}
		}
		
	}
	
	private static function createInitialMatchData(WebSoccer $websoccer, DbConnection $db, $matchinfo) {
		
		// delete any match report items, in case a previous initial simulation failed in between.
		$db->queryDelete($websoccer->getConfig('db_prefix') . '_spiel_berechnung', 'spiel_id = %d', $matchinfo['match_id']);
		$db->queryDelete($websoccer->getConfig('db_prefix') . '_matchreport', 'match_id = %d', $matchinfo['match_id']);
		
		// create model
		$homeOffensive = ($matchinfo['home_formation_offensive'] > 0) ? $matchinfo['home_formation_offensive'] : $websoccer->getConfig('sim_createformation_without_manager_offensive');
		$guestOffensive = ($matchinfo['guest_formation_offensive'] > 0) ? $matchinfo['guest_formation_offensive'] : $websoccer->getConfig('sim_createformation_without_manager_offensive');
		
		$homeTeam = new SimulationTeam($matchinfo['home_id'], $homeOffensive);
		$homeTeam->setup = $matchinfo['home_formation_setup'];
		$homeTeam->isNationalTeam = $matchinfo['home_nationalteam'];
		$homeTeam->isManagedByInterimManager = $matchinfo['home_interimmanager'];
		$homeTeam->name = $matchinfo['home_name'];
		$homeTeam->longPasses = $matchinfo['home_formation_longpasses'];
		$homeTeam->counterattacks = $matchinfo['home_formation_counterattacks'];
		
		$guestTeam = new SimulationTeam($matchinfo['guest_id'], $guestOffensive);
		$guestTeam->setup = $matchinfo['guest_formation_setup'];
		$guestTeam->isNationalTeam = $matchinfo['guest_nationalteam'];
		$guestTeam->isManagedByInterimManager = $matchinfo['guest_interimmanager'];
		$guestTeam->name = $matchinfo['guest_name'];
		$guestTeam->longPasses = $matchinfo['guest_formation_longpasses'];
		$guestTeam->counterattacks = $matchinfo['guest_formation_counterattacks'];
		
		$match = new SimulationMatch($matchinfo['match_id'], $homeTeam, $guestTeam, 0);
		$match->type = $matchinfo['type'];
		$match->penaltyShootingEnabled = $matchinfo['penaltyshooting'];
		$match->cupName = $matchinfo['cup_name'];
		$match->cupRoundName = $matchinfo['cup_roundname'];
		$match->cupRoundGroup = $matchinfo['cup_groupname'];
		$match->isAtForeignStadium = ($matchinfo['custom_stadium_id']) ? TRUE : FALSE;
			
		if (!$matchinfo['home_formation_id'] && !$matchinfo['guest_formation_id']) {
			self::handleBothTeamsHaveNoFormation($websoccer, $db, $homeTeam, $guestTeam, $match);
		} else if (!$matchinfo['home_formation_id']) {
			self::handleOneTeamHasNoFormation($websoccer, $db, $homeTeam, $match);
			
			self::addPlayers($websoccer, $db, $match->guestTeam, $matchinfo, 'guest');
			self::addSubstitution($websoccer, $db, $match->guestTeam, $matchinfo, 'guest');
		} else if (!$matchinfo['guest_formation_id']) {
			self::handleOneTeamHasNoFormation($websoccer, $db, $guestTeam, $match);
			
			self::addPlayers($websoccer, $db, $match->homeTeam, $matchinfo, 'home');
			self::addSubstitution($websoccer, $db, $match->homeTeam, $matchinfo, 'home');
		} else {
		
			self::addPlayers($websoccer, $db, $match->homeTeam, $matchinfo, 'home');
			self::addPlayers($websoccer, $db, $match->guestTeam, $matchinfo, 'guest');
			
			self::addSubstitution($websoccer, $db, $match->homeTeam, $matchinfo, 'home');
			self::addSubstitution($websoccer, $db, $match->guestTeam, $matchinfo, 'guest');
		}
		
		return $match;
	}
	
	public static function addPlayers(WebSoccer $websoccer, DbConnection $db, SimulationTeam $team, $matchinfo, $columnPrefix) {
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_spieler';
		
		$columns['verein_id'] = 'team_id';
		$columns['nation'] = 'nation';
		$columns['position'] = 'position';
		$columns['position_main'] = 'mainPosition';
		$columns['position_second'] = 'secondPosition';
		$columns['vorname'] = 'firstName';
		$columns['nachname'] = 'lastName';
		$columns['kunstname'] = 'pseudonym';
		$columns['w_staerke'] = 'strength';
		$columns['w_technik'] = 'technique';
		$columns['w_kondition'] = 'stamina';
		$columns['w_frische'] = 'freshness';
		$columns['w_zufriedenheit'] = 'satisfaction';
		$columns['st_spiele'] = 'matches_played';
		
		if ($websoccer->getConfig('players_aging') == 'birthday') {
			$ageColumn = 'TIMESTAMPDIFF(YEAR,geburtstag,CURDATE())';
		} else {
			$ageColumn = 'age';
		}
		$columns[$ageColumn] = 'age';

		$whereCondition = 'id = %d AND verletzt = 0';
		
		// player must not be blocked
		if ($team->isNationalTeam) {
			$whereCondition .= ' AND gesperrt_nationalteam = 0';
		} elseif ($matchinfo['type'] == 'Pokalspiel') {
			$whereCondition .= ' AND gesperrt_cups = 0';
		} elseif ($matchinfo['type'] != 'Freundschaft') {
			$whereCondition .= ' AND gesperrt = 0';
		}
		
		$positionMapping = SimulationHelper::getPositionsMapping();
		
		$addedPlayers = 0;
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			$playerId = $matchinfo[$columnPrefix . '_formation_player' . $playerNo];
			$mainPosition =  $matchinfo[$columnPrefix . '_formation_player_pos_' . $playerNo];
			
			$result = $db->querySelect($columns, $fromTable, $whereCondition, $playerId);
			$playerinfo = $result->fetch_array();
			$result->free();
			
			// is player still in team?
			if (isset($playerinfo['team_id']) && $playerinfo['team_id'] == $team->id
					|| $team->isNationalTeam && $playerinfo['nation'] == $team->name) {
				
				$position = $positionMapping[$mainPosition];
				
				$strength = $playerinfo['strength'];
				
				// player becomes weaker: wrong position
				if ($playerinfo['position'] != $position 
						&& $playerinfo['mainPosition'] != $mainPosition 
						&& $playerinfo['secondPosition'] != $mainPosition) {
					$strength = round($strength * (1 - $websoccer->getConfig('sim_strength_reduction_wrongposition') / 100));
					
					// player becomes weaker: secondary position
				} elseif (strlen($playerinfo['mainPosition']) && $playerinfo['mainPosition'] != $mainPosition &&
						($playerinfo['position'] == $position || $playerinfo['secondPosition'] == $mainPosition)) {
					$strength = round($strength * (1 - $websoccer->getConfig('sim_strength_reduction_secondary') / 100));
				}
				
				$player = new SimulationPlayer($playerId, $team, $position, $mainPosition, 
						3.0, $playerinfo['age'], $strength, $playerinfo['technique'], $playerinfo['stamina'], 
						$playerinfo['freshness'], $playerinfo['satisfaction']);
				
				if (strlen($playerinfo['pseudonym'])) {
					$player->name = $playerinfo['pseudonym'];
				} else {
					$player->name = $playerinfo['firstName'] . ' ' . $playerinfo['lastName'];
				}
				
				$team->positionsAndPlayers[$player->position][] = $player;
				
				SimulationStateHelper::createSimulationRecord($websoccer, $db, $matchinfo['match_id'], $player);
				
				$addedPlayers++;
				
				// is player the team captain?
				if ($matchinfo[$columnPrefix . '_captain_id'] == $playerId) {
					self::computeMorale($player, $playerinfo['matches_played']);
				}
				
				// is player free kick taker?
				if ($matchinfo[$columnPrefix . '_formation_freekickplayer'] == $playerId) {
					$team->freeKickPlayer = $player;
				}
			}
		}
		
		// generate new formation if formation is invalid
		if ($addedPlayers < 11
				&& $websoccer->getConfig('sim_createformation_on_invalidsubmission')) {
			
			// delete existing invalid formation
			$db->queryDelete($websoccer->getConfig('db_prefix') . '_spiel_berechnung', 'spiel_id = %d AND team_id = %d', array($matchinfo['match_id'], $team->id));
			$team->positionsAndPlayers = array();
			
			// generate a new one
			SimulationFormationHelper::generateNewFormationForTeam($websoccer, $db, $team, $matchinfo['match_id']);
			$team->noFormationSet = TRUE;
			return;
		}
		
		// bench
		for ($playerNo = 1; $playerNo <= 5; $playerNo++) {
			$playerId = $matchinfo[$columnPrefix . '_formation_bench' . $playerNo];
				
			$result = $db->querySelect($columns, $fromTable, $whereCondition, $playerId);
			$playerinfo = $result->fetch_array();
			$result->free();
				
			// is player still in team?
			if (isset($playerinfo['team_id']) && $playerinfo['team_id'] == $team->id
					|| $team->isNationalTeam && $playerinfo['nation'] == $team->name) {
				$player = new SimulationPlayer($playerId, $team, $playerinfo['position'], $playerinfo['mainPosition'],
						3.0, $playerinfo['age'], $playerinfo['strength'], $playerinfo['technique'], $playerinfo['stamina'],
						$playerinfo['freshness'], $playerinfo['satisfaction']);
				
				if (strlen($playerinfo['pseudonym'])) {
					$player->name = $playerinfo['pseudonym'];
				} else {
					$player->name = $playerinfo['firstName'] . ' ' . $playerinfo['lastName'];
				}
		
				$team->playersOnBench[$playerId] = $player;
				
				SimulationStateHelper::createSimulationRecord($websoccer, $db, $matchinfo['match_id'], $player, TRUE);
			}
		}
		
	}
	
	private static function addSubstitution(WebSoccer $websoccer, DbConnection $db, SimulationTeam $team, $matchinfo, $columnPrefix) {
		
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if ($matchinfo[$columnPrefix . '_formation_sub' . $subNo . '_out']) {
				$out = $matchinfo[$columnPrefix . '_formation_sub' . $subNo . '_out'];
				$in = $matchinfo[$columnPrefix . '_formation_sub' . $subNo . '_in'];
				$minute = $matchinfo[$columnPrefix . '_formation_sub' . $subNo . '_minute'];
				$condition = $matchinfo[$columnPrefix . '_formation_sub' . $subNo . '_condition'];
				$position = $matchinfo[$columnPrefix . '_formation_sub' . $subNo . '_position'];
				
				if (isset($team->playersOnBench[$in])) {
					$playerIn = $team->playersOnBench[$in];
					
					$playerOut = self::findPlayerOnField($team, $out);
					
					if ($playerIn && $playerOut) {
						$sub = new SimulationSubstitution($minute, $playerIn, $playerOut, $condition, $position);
						$team->substitutions[] = $sub;
					}
				}
				
			}
				
		}
		
	}
	
	private function findPlayerOnField(SimulationTeam $team, $playerId) {
		foreach ($team->positionsAndPlayers as $position => $players) {
			foreach ($players as $player) {
				if ($player->id == $playerId) {
					return $player;
				}
			}
		}
		
		return false;
	}
	
	private static function teamHasNoManager(WebSoccer $websoccer, DbConnection $db, $teamId) {
		// query user id
		$columns = 'user_id';
		$fromTable = $websoccer->getConfig('db_prefix') . '_verein';
		$whereCondition = 'id = %d';
		$parameters = $teamId;
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$teaminfo = $result->fetch_array();
		$result->free();
		
		return !(isset($teaminfo['user_id']) && $teaminfo['user_id']);
	}
	
	private static function computeMorale(SimulationPlayer $captain, $matchesPlayed) {
		// morale is at the moment completely dependend on team captain
		$morale = 0;
		
		// consider age: Every year gives 5%. Example: a 30 years old player brings 70%.
		$morale += ($captain->age - 16) * 5; 
		
		// consider number of played matches. Bonus of up to 40%, if player had 100+ matches.
		$morale += min(40, round($matchesPlayed / 2));
		
		// consider strength. Weak teams will be too strong otherwise.
		$morale = $morale * $captain->strength / 100;
		
		$morale = min(100, max(0, $morale));
		$captain->team->morale = $morale;
	}
	
}

?>
