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
 * Helper functions for setting formations for the match simulation.
 * 
 * @author Ingo Hofmann
 */
class SimulationFormationHelper {
	
	/**
	 * Generates a new formation for the specified team, which will be directly stored both in the database and in the internal model.
	 * 
	 * It is a 4-4-2 formation. It always selects the freshest players of the team.
	 * 
	 * @param WebSoccer $websoccer request context.
	 * @param DbConnection $db database connection.
	 * @param SimulationTeam $team Team that needs a new formation.
	 * @param int $matchId match id.
	 */
	public static function generateNewFormationForTeam(WebSoccer $websoccer, DbConnection $db, SimulationTeam $team, $matchId) {
		
		// get all players (prefer the freshest players)
		$columns['id'] = 'id';
		$columns['position'] = 'position';
		$columns['position_main'] = 'mainPosition';
		$columns['vorname'] = 'firstName';
		$columns['nachname'] = 'lastName';
		$columns['kunstname'] = 'pseudonym';
		$columns['w_staerke'] = 'strength';
		$columns['w_technik'] = 'technique';
		$columns['w_kondition'] = 'stamina';
		$columns['w_frische'] = 'freshness';
		$columns['w_zufriedenheit'] = 'satisfaction';
		
		if ($websoccer->getConfig('players_aging') == 'birthday') {
			$ageColumn = 'TIMESTAMPDIFF(YEAR,geburtstag,CURDATE())';
		} else {
			$ageColumn = 'age';
		}
		$columns[$ageColumn] = 'age';
		
		// get players from usual team
		if (!$team->isNationalTeam) {
			$fromTable = $websoccer->getConfig('db_prefix') . '_spieler';
			$whereCondition = 'verein_id = %d AND verletzt = 0 AND gesperrt = 0 AND status = 1 ORDER BY w_frische DESC';
			$parameters = $team->id;
			$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		} else {
			// national team: take best players of nation
			$columnsStr = '';
			
			$firstColumn = TRUE;
			foreach($columns as $dbName => $aliasName) {
				if (!$firstColumn) {
					$columnsStr = $columnsStr .', ';
				} else {
					$firstColumn = FALSE;
				}
			
				$columnsStr = $columnsStr . $dbName. ' AS '. $aliasName;
			}
			
			$nation = $db->connection->escape_string($team->name);
			$dbPrefix = $websoccer->getConfig('db_prefix');
			$queryStr = '(SELECT ' . $columnsStr . ' FROM ' . $dbPrefix . '_spieler WHERE nation = \''. $nation . '\' AND position = \'Torwart\' ORDER BY w_staerke DESC, w_frische DESC LIMIT 1)';
			$queryStr .= ' UNION ALL (SELECT ' . $columnsStr . ' FROM ' . $dbPrefix . '_spieler WHERE nation = \''. $nation . '\' AND position = \'Abwehr\' ORDER BY w_staerke DESC, w_frische DESC LIMIT 4)';
			$queryStr .= ' UNION ALL (SELECT ' . $columnsStr . ' FROM ' . $dbPrefix . '_spieler WHERE nation = \''. $nation . '\' AND position = \'Mittelfeld\' ORDER BY w_staerke DESC, w_frische DESC LIMIT 4)';
			$queryStr .= ' UNION ALL (SELECT ' . $columnsStr . ' FROM ' . $dbPrefix . '_spieler WHERE nation = \''. $nation . '\' AND position = \'Sturm\' ORDER BY w_staerke DESC, w_frische DESC LIMIT 2)';
			$result = $db->executeQuery($queryStr);
		}
		
		$lvExists = FALSE;
		$rvExists = FALSE;
		$lmExists = FALSE;
		$rmExists = FALSE;
		$ivPlayers = 0;
		$zmPlayers = 0;
		
		while ($playerinfo = $result->fetch_array()) {
			$position = $playerinfo['position'];
			
			// generate a 4-4-2 formation
			if ($position == PLAYER_POSITION_GOALY 
					&& isset($team->positionsAndPlayers[PLAYER_POSITION_GOALY])
					&& count($team->positionsAndPlayers[PLAYER_POSITION_GOALY]) == 1
					|| $position == PLAYER_POSITION_DEFENCE 
						&& isset($team->positionsAndPlayers[PLAYER_POSITION_DEFENCE])
						&& count($team->positionsAndPlayers[PLAYER_POSITION_DEFENCE]) >= 4
					|| $position == PLAYER_POSITION_MIDFIELD 
						&& isset($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD])
						&& count($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD]) >= 4
					|| $position == PLAYER_POSITION_STRIKER
						&& isset($team->positionsAndPlayers[PLAYER_POSITION_STRIKER])
						&& count($team->positionsAndPlayers[PLAYER_POSITION_STRIKER]) >= 2) {
				continue;
			}
			
			
			$mainPosition = $playerinfo['mainPosition'];
			//prevent double LV/RV/LM/RM
			if ($mainPosition == 'LV') {
				if ($lvExists) {
					$mainPosition = 'IV';
					$ivPlayers++;
					if ($ivPlayers == 3) {
						$mainPosition = 'RV';
						$rvExists = TRUE;
					}
				} else {
					$lvExists = TRUE;
				}
			} elseif ($mainPosition == 'RV') {
				if ($rvExists) {
					$mainPosition = 'IV';
					$ivPlayers++;
					if ($ivPlayers == 3) {
						$mainPosition = 'LV';
						$lvExists = TRUE;
					}
				} else {
					$rvExists = TRUE;
				}
			} elseif ($mainPosition == 'IV') {
				$ivPlayers++;
				if ($ivPlayers == 3) {
					if (!$rvExists) {
						$mainPosition = 'RV';
						$rvExists = TRUE;
					} else {
						$mainPosition = 'LV';
						$lvExists = TRUE;
					}
				}
			} elseif ($mainPosition == 'LM') {
				if ($lmExists) {
					$mainPosition = 'ZM';
					$zmPlayers++;
				} else {
					$lmExists = TRUE;
				}
			} elseif ($mainPosition == 'RM') {
				if ($rmExists) {
					$mainPosition = 'ZM';
					$zmPlayers++;
				} else {
					$rmExists = TRUE;
				}
			} elseif ($mainPosition == 'LS' || $mainPosition == 'RS') {
				$mainPosition = 'MS';
			} elseif ($mainPosition == 'ZM') {
				$zmPlayers++;
				if ($zmPlayers > 2) {
					$mainPosition = 'DM';
				}
			}
			
			$player = new SimulationPlayer($playerinfo['id'], $team, $position, $mainPosition,
					3.0, $playerinfo['age'], $playerinfo['strength'], $playerinfo['technique'], $playerinfo['stamina'],
					$playerinfo['freshness'], $playerinfo['satisfaction']);
			
			if (strlen($playerinfo['pseudonym'])) {
				$player->name = $playerinfo['pseudonym'];
			} else {
				$player->name = $playerinfo['firstName'] . ' ' . $playerinfo['lastName'];
			}
			
			
			$team->positionsAndPlayers[$player->position][] = $player;
			SimulationStateHelper::createSimulationRecord($websoccer, $db, $matchId, $player);
		}
		$result->free();
	}
	
}
?>