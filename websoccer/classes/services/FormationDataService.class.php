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
 * Data service for leagues
 */
class FormationDataService {
	
	/**
	 * Provides a previously saved formation of the specified team and match.
	 * 
	 * @param WebSoccer $websoccer Application context
	 * @param DbConnection $db DB connection
	 * @param int $teamId ID of team.
	 * @param int $matchId ID of match
	 * @return array previously set formation.
	 */
	public static function getFormationByTeamId(WebSoccer $websoccer, DbConnection $db, $teamId, $matchId) {
		$whereCondition = 'verein_id = %d AND match_id = %d';
		$parameters = array($teamId, $matchId);
		
		return self::_getFormationByCondition($websoccer, $db, $whereCondition, $parameters);
	}
	
	/**
	 * Provides a previously saved formation as template.
	 *
	 * @param WebSoccer $websoccer Application context
	 * @param DbConnection $db DB connection
	 * @param int $teamId ID of team.
	 * @param int $templateId ID of template (formation)
	 * @return array formation.
	 */
	public static function getFormationByTemplateId(WebSoccer $websoccer, DbConnection $db, $teamId, $templateId) {
		$whereCondition = 'id = %d AND verein_id = %d';
		$parameters = array($templateId, $teamId);
		return self::_getFormationByCondition($websoccer, $db, $whereCondition, $parameters);
	}
	
	private static function _getFormationByCondition(WebSoccer $websoccer, DbConnection $db, $whereCondition, $parameters) {
		$fromTable = $websoccer->getConfig('db_prefix') . '_aufstellung';
	
		// select
		$columns['id'] = 'id';
		$columns['offensive'] = 'offensive';
		$columns['setup'] = 'setup';
		$columns['longpasses'] = 'longpasses';
		$columns['counterattacks'] = 'counterattacks';
		$columns['freekickplayer'] = 'freekickplayer';
	
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			$columns['spieler' . $playerNo] = 'player' . $playerNo;
			$columns['spieler' . $playerNo . '_position'] = 'player' . $playerNo . '_pos';
		}
	
		for ($playerNo = 1; $playerNo <= 5; $playerNo++) {
			$columns['ersatz' . $playerNo] = 'bench' . $playerNo;
		}
	
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			$columns['w'. $subNo . '_raus'] = 'sub' . $subNo .'_out';
			$columns['w'. $subNo . '_rein'] = 'sub' . $subNo .'_in';
			$columns['w'. $subNo . '_minute'] = 'sub' . $subNo .'_minute';
			$columns['w'. $subNo . '_condition'] = 'sub' . $subNo .'_condition';
			$columns['w'. $subNo . '_position'] = 'sub' . $subNo .'_position';
		}
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$formation = $result->fetch_array();
		if (!$formation) {
			$formation = array();
		}
		$result->free();
	
		return $formation;
	}
	
	/**
	 * Provides a proposal for a formation, considering the specified formation setup and sort column.
	 * 
	 * @param WebSoccer $websoccer Application Conttext
	 * @param DbConnection $db DB connection
	 * @param int $teamId ID of team
	 * @param int $setupDefense number of players in defense
	 * @param int $setupDM number of players in defensive midfield
	 * @param int $setupMidfield number of players in midfield
	 * @param int $setupOM number of players in offensive midfield
	 * @param int $setupStriker number of players in forward area (center forward only)
	 * @param int $setupOutsideforward number of outside forwards
	 * @param int $sortColumn DB sort column name
	 * @param string $sortDirection ASC|DESC (sort direction)
	 * @param boolean $isNationalteam TRUE if team is a national team.
	 * @return array array of players. Each player is an array with keys {id, position}.
	 */
	public static function getFormationProposalForTeamId(WebSoccer $websoccer, DbConnection $db, $teamId, $setupDefense, 
			$setupDM, $setupMidfield, $setupOM, $setupStriker, $setupOutsideforward, $sortColumn, $sortDirection = 'DESC', 
			$isNationalteam = FALSE, $isCupMatch = FALSE) {
				
		$columns = 'id,position,position_main,position_second';
		
		if (!$isNationalteam) {
			$fromTable = $websoccer->getConfig('db_prefix') . '_spieler';
			$whereCondition = 'verein_id = %d AND gesperrt';
			if ($isCupMatch) {
				$whereCondition .= '_cups';
			}
			$whereCondition .= ' = 0 AND verletzt = 0 AND status = 1';
		} else {
			$fromTable = $websoccer->getConfig('db_prefix') . '_spieler AS P';
			$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_nationalplayer AS NP ON NP.player_id = P.id';
			$whereCondition = 'NP.team_id = %d AND gesperrt_nationalteam = 0 AND verletzt = 0 AND status = 1';
		}
		
		$whereCondition .=	' ORDER BY '. $sortColumn . ' ' . $sortDirection;
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $teamId);

		// determine open positions
		$openPositions['T'] = 1;
		
		// defense positions
		if ($setupDefense < 4) {
			$openPositions['IV'] = $setupDefense;
			$openPositions['LV'] = 0;
			$openPositions['RV'] = 0;
		} else {
			$openPositions['LV'] = 1;
			$openPositions['RV'] = 1;
			$openPositions['IV'] = $setupDefense - 2;
		}
		
		// defensive midfield positions
		$openPositions['DM'] = $setupDM;
		$openPositions['OM'] = $setupOM;
		
		// midfield positions
		if ($setupMidfield == 1) {
			$openPositions['ZM'] = 1;
		} else if ($setupMidfield == 2) {
			$openPositions['LM'] = 1;
			$openPositions['RM'] = 1;
		} else if ($setupMidfield == 3) {
			$openPositions['LM'] = 1;
			$openPositions['ZM'] = 1;
			$openPositions['RM'] = 1;
		} else if ($setupMidfield >= 4) {
			$openPositions['LM'] = 1;
			$openPositions['ZM'] = $setupMidfield - 2;
			$openPositions['RM'] = 1;
		} else {
			$openPositions['LM'] = 0;
			$openPositions['ZM'] = 0;
			$openPositions['RM'] = 0;
		}
		
		// strikers
		$openPositions['MS'] = $setupStriker;
		
		// outside forward
		if ($setupOutsideforward == 2) {
			$openPositions['LS'] = 1;
			$openPositions['RS'] = 1;
		} else {
			$openPositions['LS'] = 0;
			$openPositions['RS'] = 0;
		}
		
		$players = array();
		$unusedPlayers = array();
		while ($player = $result->fetch_array()) {
			
			$added = FALSE;
			
			// handle players without main position (all-rounder)
			if (!strlen($player['position_main'])) {
				
				if ($player['position'] == 'Torwart') {
					$possiblePositions = array('T');
				} elseif ($player['position'] == 'Abwehr') {
					$possiblePositions = array('LV', 'IV', 'RV');
				} elseif ($player['position'] == 'Mittelfeld') {
					$possiblePositions = array('RM', 'ZM', 'LM', 'RM', 'DM', 'OM');
				} else {
					$possiblePositions = array('LS', 'MS', 'RS');
				}
				
				foreach($possiblePositions as $possiblePosition) {
					if ($openPositions[$possiblePosition]) {
						$openPositions[$possiblePosition] = $openPositions[$possiblePosition] - 1;
						$players[] = array('id' => $player['id'], 'position' => $possiblePosition);
						$added = TRUE;
						break;
					}
				}
				
				// add at main position
			} elseif (strlen($player['position_main']) && isset($openPositions[$player['position_main']]) && $openPositions[$player['position_main']]) {
				$openPositions[$player['position_main']] = $openPositions[$player['position_main']] - 1;
				$players[] = array('id' => $player['id'], 'position' => $player['position_main']);
				$added = TRUE;
			}
			
			// remember player for later if no space on his main position. Might be used with his secondary position, if he has any.
			if (!$added && strlen($player['position_second'])) {
				$unusedPlayers[] = $player;
			}
			
		}
		$result->free();
		
		// there might not be enough players with matching main positions, hence use players with secondary position
		foreach ($openPositions as $position => $requiredPlayers) {
			for ($i = 0; $i < $requiredPlayers; $i++) {
				for ($playerIndex = 0; $playerIndex < count($unusedPlayers); $playerIndex++) {
					if ($unusedPlayer[$playerIndex]['position_second'] == $position) {
						$players[] = array('id' => $unusedPlayer[$playerIndex]['id'], 'position' => $unusedPlayer[$playerIndex]['position_second']);
						unset($unusedPlayer[$playerIndex]);
						break;
					}
					
				}
			}
		}
		
		return $players;
	}
	
}
?>