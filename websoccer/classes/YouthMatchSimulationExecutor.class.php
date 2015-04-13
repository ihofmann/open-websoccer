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
define('DEFAULT_YOUTH_OFFENSIVE', 60);
define('DEFAULT_PLAYER_AGE', 20);
define('MIN_NUMBER_OF_PLAYERS', 9);
define('YOUTH_MATCH_TYPE', 'Youth');

define('YOUTH_STRENGTH_STAMINA', 100);
define('YOUTH_STRENGTH_FRESHNESS', 100);
define('YOUTH_STRENGTH_SATISFACTION', 100);

/**
 * Finds matches to simulate, initializes them and triggers their simulation.
 */
class YouthMatchSimulationExecutor {
	
	/**
	 * Simulates youth matches. They are executed differently from normal matches sinc they have a different structure
	 * and other simulation observers.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB conncetion.
	 * @param int $maxMatchesToSimulate Maximum number of matches to simulate.
	 */
	public static function simulateOpenYouthMatches(WebSoccer $websoccer, DbConnection $db, $maxMatchesToSimulate) {
		
		// is feature enabled at the moment?
		if (!$websoccer->getConfig('youth_enabled')) {
			return;
		}
		
		$simulator = new Simulator($db, $websoccer);
		
		// observer stores results
		$simulator->attachObserver(new YouthMatchDataUpdateSimulatorObserver($websoccer, $db));
		
		// ceate match report items on events
		$simulator->getSimulationStrategy()->attachObserver(new YouthMatchReportSimulationObserver($websoccer, $db));
		
		// get matches to simulate
		$result = $db->querySelect('*', $websoccer->getConfig('db_prefix') . '_youthmatch', 
				'simulated != \'1\' AND matchdate <= %d ORDER BY matchdate ASC', $websoccer->getNowAsTimestamp(), $maxMatchesToSimulate);
		while ($matchinfo = $result->fetch_array()) {
			$match = self::_createMatch($websoccer, $db, $matchinfo);
				
			if ($match != null) {
				$simulator->simulateMatch($match, 100);
				
				// let garbage collector free memory before script execution by removing all references to objects
				$match->cleanReferences();
				unset($match);
			}
		}
		$result->free();
	}
	
	/**
	 * Builds the simulation model for a specified match.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param array $matchinfo Match data from DB.
	 * @return SimulationMatch internal simulation model.
	 */
	private static function _createMatch(WebSoccer $websoccer, DbConnection $db, $matchinfo) {
		
		$homeTeam = new SimulationTeam($matchinfo['home_team_id'], DEFAULT_YOUTH_OFFENSIVE);
		$guestTeam = new SimulationTeam($matchinfo['guest_team_id'], DEFAULT_YOUTH_OFFENSIVE);
		
		$match = new SimulationMatch($matchinfo['id'], $homeTeam, $guestTeam, 0);
		$match->type = YOUTH_MATCH_TYPE;
		$match->penaltyShootingEnabled = FALSE;
		
		self::_addPlayers($websoccer, $db, $match, $homeTeam);
		self::_addSubstitutions($websoccer, $db, $match, $homeTeam, $matchinfo, 'home');
		
		self::_addPlayers($websoccer, $db, $match, $guestTeam);
		self::_addSubstitutions($websoccer, $db, $match, $guestTeam, $matchinfo, 'guest');
		
		return $match;
	}
	
	/**
	 * Adds players to the team model. First tries to query in a formation saved players. If not enough, then
	 * creates a random formation.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param SimulationMatch $match match model.
	 * @param SimulationTeam $team team model.
	 */
	private static function _addPlayers(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match, SimulationTeam $team) {
		
		// query set players
		$fromTable = $websoccer->getConfig('db_prefix') . '_youthmatch_player AS MP';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_youthplayer AS P ON P.id = MP.player_id';
		
		// ensure that player still is in team in WHERE condition.
		$whereCondition = 'MP.match_id = %d AND MP.team_id = %d AND P.team_id = %d ORDER BY playernumber ASC';
		$parameters = array($match->id, $team->id, $team->id);
		
		$columns = array(
				'P.id' => 'id',
				'P.strength' => 'player_strength',
				'P.firstname' => 'firstname',
				'P.lastname' => 'lastname',
				'P.position' => 'player_position',
				'MP.position' => 'match_position',
				'MP.position_main' => 'match_position_main',
				'MP.grade' => 'grade',
				'MP.state' => 'state'
				);
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		
		$addedFieldPlayers = 0;
		while ($playerinfo = $result->fetch_array()) {
			
			$name = $playerinfo['firstname'] . ' ' . $playerinfo['lastname'];
			
			// default values
			$strength = $playerinfo['player_strength'];
			$technique = $strength;
			$position = $playerinfo['player_position'];
			$mainPosition = $playerinfo['match_position_main'];
			
			$player = new SimulationPlayer($playerinfo['id'], $team, $position, $mainPosition, $playerinfo['grade'], 
					DEFAULT_PLAYER_AGE, $strength, $technique, 
					YOUTH_STRENGTH_STAMINA, YOUTH_STRENGTH_FRESHNESS, YOUTH_STRENGTH_SATISFACTION);
			$player->name = $name;
			
			// bench player
			if ($playerinfo['state'] == 'Ersatzbank') {
				$team->playersOnBench[$playerinfo['id']] = $player;
				
				// field player
			} else {
				
				// first player must be always goalkeeper
				if ($addedFieldPlayers == 0) {
					$player->position = 'Torwart';
					$player->mainPosition = 'T';
				} else {
					$player->position = $playerinfo['match_position'];
				}
				
				// strength adaption required?
				if ($player->position != $playerinfo['player_position']) {
					$player->strength = round($strength * (1 - $websoccer->getConfig('sim_strength_reduction_wrongposition') / 100));
				}
				
				$team->positionsAndPlayers[$player->position][] = $player;
				
				$addedFieldPlayers++;
			}
		}
		$result->free();
		
		if ($addedFieldPlayers < MIN_NUMBER_OF_PLAYERS) {
			$team->noFormationSet = TRUE;
			
			self::_createRandomFormation($websoccer, $db, $match, $team);
		}
	}
	
	/**
	 * Creates a new formation for specified team.
	 * Will simply take the first 11 players and place them in a 4-4-2 formation.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param SimulationMatch $match match model.
	 * @param SimulationTeam $team team model.
	 */
	private static function _createRandomFormation(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match, SimulationTeam $team) {
		// better delete possible previous formation with too few players
		$db->queryDelete($websoccer->getConfig('db_prefix') . '_youthmatch_player', 'match_id = %d AND team_id = %d', array($match->id, $team->id));
		
		// define the exact default formation
		$formationPositions = array('T', 'LV', 'IV', 'IV', 'RV', 'LM', 'ZM', 'ZM', 'RM', 'LS', 'RS');
		$positionMapping = SimulationHelper::getPositionsMapping();
		
		// set players
		$players = YouthPlayersDataService::getYouthPlayersOfTeam($websoccer, $db, $team->id);
		$positionIndex = 0;
		foreach ($players as $playerinfo) {
			$mainPosition = $formationPositions[$positionIndex];
			$position = $positionMapping[$mainPosition];
			
			$player = new SimulationPlayer($playerinfo['id'], $team, $position, $mainPosition, 3.0,
					DEFAULT_PLAYER_AGE, $playerinfo['strength'], $playerinfo['strength'],
					YOUTH_STRENGTH_STAMINA, YOUTH_STRENGTH_FRESHNESS, YOUTH_STRENGTH_SATISFACTION);
			$player->name = $playerinfo['firstname'] . ' ' . $playerinfo['lastname'];
			
			// strength adaption required?
			if ($player->position != $playerinfo['position']) {
				$player->strength = round($playerinfo['strength'] * (1 - $websoccer->getConfig('sim_strength_reduction_wrongposition') / 100));
			}
			
			try {
				// create record
				$columns = array(
						'match_id' => $match->id,
						'team_id' => $team->id,
						'player_id' => $player->id,
						'playernumber' => $positionIndex + 1,
						'position' => $player->position,
						'position_main' => $player->mainPosition,
						'name' => $player->name
						);
				$db->queryInsert($columns, $websoccer->getConfig('db_prefix') . '_youthmatch_player');
				
				$team->positionsAndPlayers[$player->position][] = $player;
			} catch (Exception $e) {
				// could not be stored. Can happen when the youth player moved from the opponent to this team.
				// then we get a PK violation. We just don't add this player then.
			}
			
			$positionIndex++;
			
			if ($positionIndex == 11) {
				break;
			}
		}
	}
	
	/**
	 * Reads the planned substitutions and adds them to the model
	 * 
	 * @param WebSoccer $websoccer
	 * @param DbConnection $db
	 * @param SimulationMatch $match
	 * @param SimulationTeam $team
	 * @param array $matchinfo
	 * @param string $teamPrefix home|guest
	 */
	private static function _addSubstitutions(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match, SimulationTeam $team, $matchinfo, $teamPrefix) {
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if ($matchinfo[$teamPrefix . '_s' . $subNo . '_out']) {
				$out = $matchinfo[$teamPrefix . '_s' . $subNo . '_out'];
				$in = $matchinfo[$teamPrefix . '_s' . $subNo . '_in'];
				$minute = $matchinfo[$teamPrefix . '_s' . $subNo . '_minute'];
				$condition = $matchinfo[$teamPrefix . '_s' . $subNo . '_condition'];
				$position = $matchinfo[$teamPrefix . '_s' . $subNo . '_position'];
				
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
}