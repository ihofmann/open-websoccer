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
 * Helps considering whether a cup match needs to be extended
 * and helps generating matches.
 * 
 * @author Ingo Hofmann
 */
class SimulationCupMatchHelper {
	
	/**
	 * Checks if the specified cup match requires after-time or penalty shooting, due to a draw. Also considering the result of
	 * a frst round if match is second-round match (will be detected automatically).
	 * It also automatically creates matches for a next round in case there is already a winner.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB Connection.
	 * @param SimulationMatch $match match to check for.
	 * @return boolean TRUE if match has no winner, but needs one. FALSE is match can be completed.
	 */
	public static function checkIfExtensionIsRequired(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match) {
		
		// do no play extension if it is a group match
		if (strlen($match->cupRoundGroup)) {
			return FALSE;
		}
		
		// check if there was a first round
		$columns['home_tore'] = 'home_goals';
		$columns['gast_tore'] = 'guest_goals';
		$columns['berechnet'] = 'is_simulated';
		
		$whereCondition = 'home_verein = %d AND gast_verein = %d AND pokalname = \'%s\' AND pokalrunde = \'%s\'';
		$result = $db->querySelect($columns, $websoccer->getConfig('db_prefix') . '_spiel', $whereCondition,
				array($match->guestTeam->id, $match->homeTeam->id, $match->cupName, $match->cupRoundName), 1);
		$otherRound = $result->fetch_array();
		$result->free();
		
		// case: this is the first round (of two). So no need to play extension time
		if (isset($otherRound['is_simulated']) && !$otherRound['is_simulated']) {
			return FALSE;
		}
		
		// case: there is no second match. So we need a winner here.
		if (!$otherRound) {
			
			// no winner
			if ($match->homeTeam->getGoals() == $match->guestTeam->getGoals()) {
				return TRUE;
				
				// home team won
			} elseif ($match->homeTeam->getGoals() > $match->guestTeam->getGoals()) {
				self::createNextRoundMatchAndPayAwards($websoccer, $db, 
						$match->homeTeam->id, $match->guestTeam->id, $match->cupName, $match->cupRoundName);
				return FALSE;
				
				// guest team won
			} else {
				self::createNextRoundMatchAndPayAwards($websoccer, $db,
						$match->guestTeam->id, $match->homeTeam->id, $match->cupName, $match->cupRoundName);
				return FALSE;
			}
		}
		
		// get total result
		$totalHomeGoals = $match->homeTeam->getGoals();
		$totalGuestGoals = $match->guestTeam->getGoals();
		
		$totalHomeGoals += $otherRound['guest_goals'];
		$totalGuestGoals += $otherRound['home_goals'];
		
		$winnerTeam = null;
		$loserTeam = null;
		
		// home team won?
		if ($totalHomeGoals > $totalGuestGoals) {
			$winnerTeam =  $match->homeTeam;
			$loserTeam =  $match->guestTeam;
		// guest team
		} elseif ($totalHomeGoals < $totalGuestGoals) {
			$winnerTeam =  $match->guestTeam;
			$loserTeam =  $match->homeTeam;
		// same amount of goals, so check guest goals ('Auswärtstorregelung')
		} else {
			
			if ($otherRound['guest_goals'] > $match->guestTeam->getGoals()) {
				$winnerTeam =  $match->homeTeam;
				$loserTeam =  $match->guestTeam;
			} elseif ($otherRound['guest_goals'] < $match->guestTeam->getGoals()) {
				$winnerTeam =  $match->guestTeam;
				$loserTeam =  $match->homeTeam;
				
				// no winner
			} else {
				return TRUE;
			}
			
		}
		
		// we have a winner
		self::createNextRoundMatchAndPayAwards($websoccer, $db,
				$winnerTeam->id, $loserTeam->id, $match->cupName, $match->cupRoundName);
		return FALSE;
	}
	
	/**
	 * Pays configured cup round awards (such as per round or winner/second of final round)
	 * and creates actual matches for next cup round, if available.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB Connection-
	 * @param int $winnerTeamId ID of winner team.
	 * @param int $loserTeamId ID of loser team.
	 * @param string $cupName matche's cup name.
	 * @param string $cupRound matche's cup round name.
	 */
	public static function createNextRoundMatchAndPayAwards(WebSoccer $websoccer, DbConnection $db, 
			$winnerTeamId, $loserTeamId, $cupName, $cupRound) {
		
		// rounds and cup info
		$columns['C.id'] = 'cup_id';
		$columns['C.winner_award'] = 'cup_winner_award';
		$columns['C.second_award'] = 'cup_second_award';
		$columns['C.perround_award'] = 'cup_perround_award';
		
		$columns['R.id'] = 'round_id';
		$columns['R.finalround'] = 'is_finalround';
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_cup_round AS R';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_cup AS C ON C.id = R.cup_id';
		
		$result = $db->querySelect($columns, $fromTable,
				'C.name = \'%s\' AND R.name = \'%s\'', array($cupName, $cupRound), 1);
		$round = $result->fetch_array();
		$result->free();
		
		// do nothing if no round is configured
		if (!$round) {
			return;
		}
		
		// credit per round award
		if ($round['cup_perround_award']) {
			BankAccountDataService::creditAmount($websoccer, $db, $winnerTeamId, $round['cup_perround_award'],
				'cup_cuproundaward_perround_subject', $cupName);
			BankAccountDataService::creditAmount($websoccer, $db, $loserTeamId, $round['cup_perround_award'],
				'cup_cuproundaward_perround_subject', $cupName);
		}
		
		$result = $db->querySelect('user_id', $websoccer->getConfig('db_prefix') . '_verein', 'id = %d', $winnerTeamId);
		$winnerclub = $result->fetch_array();
		$result->free();
		
		$result = $db->querySelect('user_id', $websoccer->getConfig('db_prefix') . '_verein', 'id = %d', $loserTeamId);
		$loserclub = $result->fetch_array();
		$result->free();
		
		// create achievement log
		$now = $websoccer->getNowAsTimestamp();
		if ($winnerclub['user_id']) {
			$db->queryInsert(array(
					'user_id' => $winnerclub['user_id'],
					'team_id' => $winnerTeamId,
					'cup_round_id' => $round['round_id'],
					'date_recorded' => $now
			), $websoccer->getConfig('db_prefix') .'_achievement');
		}
		
		if ($loserclub['user_id']) {
			$db->queryInsert(array(
					'user_id' => $loserclub['user_id'],
					'team_id' => $loserTeamId,
					'cup_round_id' => $round['round_id'],
					'date_recorded' => $now
			), $websoccer->getConfig('db_prefix') .'_achievement');
		}
		
		
		// was final round?
		if ($round['is_finalround']) {
			
			// credit awards
			if ($round['cup_winner_award']) {
				BankAccountDataService::creditAmount($websoccer, $db, $winnerTeamId, $round['cup_winner_award'],
					'cup_cuproundaward_winner_subject', $cupName);
			}
			if ($round['cup_second_award']) {
				BankAccountDataService::creditAmount($websoccer, $db, $loserTeamId, $round['cup_second_award'],
					'cup_cuproundaward_second_subject', $cupName);
			}
			
			// update 'winner flag' of cup
			$db->queryUpdate(array('winner_id' => $winnerTeamId), $websoccer->getConfig('db_prefix') . '_cup', 
					'id = %d', $round['cup_id']);
			
			// award badge
			if ($winnerclub['user_id']) {
				BadgesDataService::awardBadgeIfApplicable($websoccer, $db, $winnerclub['user_id'], 'cupwinner');
			}
			
			
		// create matches for next round
		} else {
			$columns = 'id,firstround_date,secondround_date,name';
			$fromTable = $websoccer->getConfig('db_prefix') . '_cup_round';
			
			// get next round for winner
			$result = $db->querySelect($columns, $fromTable, 'from_winners_round_id = %d', $round['round_id'], 1);
			$winnerRound = $result->fetch_array();
			$result->free();
			
			if (isset($winnerRound['id'])) {
				self::createMatchForTeamAndRound($websoccer, $db, $winnerTeamId, $winnerRound['id'], 
						$winnerRound['firstround_date'], $winnerRound['secondround_date'], $cupName, $winnerRound['name']);
			}
			
			// get next round for loser
			$result = $db->querySelect($columns, $fromTable, 'from_loosers_round_id = %d', $round['round_id'], 1);
			$loserRound = $result->fetch_array();
			$result->free();
				
			if (isset($loserRound['id'])) {
				self::createMatchForTeamAndRound($websoccer, $db, $loserTeamId, $loserRound['id'],
						$loserRound['firstround_date'], $loserRound['secondround_date'], $cupName, $loserRound['name']);
			}
		}
	}
	
	public static function checkIfMatchIsLastMatchOfGroupRoundAndCreateFollowingMatches(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match) {
		
		if (!strlen($match->cupRoundGroup)) {
			return;
		}
		
		// check if there are any open matches in this round
		$result = $db->querySelect('COUNT(*) AS hits', $websoccer->getConfig('db_prefix') . '_spiel', 
				'berechnet = \'0\' AND pokalname = \'%s\' AND pokalrunde = \'%s\' AND id != %d',
				array($match->cupName, $match->cupRoundName, $match->id));
		$openMatches = $result->fetch_array();
		$result->free();
		
		// there are still matches to simulate, so wait until all potential opponents are available
		if (isset($openMatches['hits']) && $openMatches['hits']) {
			return;
		}
		
		// get configurations for next round
		$columns = array();
		$columns['N.cup_round_id'] = 'round_id';
		$columns['N.groupname'] = 'groupname';
		$columns['N.rank'] = 'rank';
		$columns['N.target_cup_round_id'] = 'target_cup_round_id';
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_cup_round_group_next AS N';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_cup_round AS R ON N.cup_round_id = R.id';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_cup AS C ON R.cup_id = C.id';
		$result = $db->querySelect($columns, $fromTable, 'C.name = \'%s\' AND R.name = \'%s\'',
				array($match->cupName, $match->cupRoundName));
		
		$nextConfigs = array();
		while ($nextConfig = $result->fetch_array()) {
			$nextConfigs[$nextConfig['groupname']]['' . $nextConfig['rank']] = $nextConfig['target_cup_round_id'];
			$roundId = $nextConfig['round_id'];
		}
		$result->free();
		
		// get teams of each group for next rounds
		$nextRoundTeams = array();
		foreach ($nextConfigs as $groupName => $rankings) {
			$teamsInGroup = CupsDataService::getTeamsOfCupGroupInRankingOrder($websoccer, $db, $roundId, $groupName);
			
			for ($teamRank = 1; $teamRank <= count($teamsInGroup); $teamRank++) {
				
				$configIndex = '' . $teamRank;
				if (isset($rankings[$configIndex])) {
					$team = $teamsInGroup[$teamRank - 1];
					$targetRound = $rankings[$configIndex];
					
					$nextRoundTeams[$targetRound][] = $team['id'];
				}
			}
		}
		
		// create matches
		$matchTable = $websoccer->getConfig('db_prefix') . '_spiel';
		$type = 'Pokalspiel';
		
		foreach ($nextRoundTeams as $nextRoundId => $teamIds) {
			
			// get round info
			$result = $db->querySelect('name,firstround_date,secondround_date', $websoccer->getConfig('db_prefix') . '_cup_round', 
					'id = %d', $nextRoundId);
			$roundInfo = $result->fetch_array();
			$result->free();
			if (!$roundInfo) {
				continue;
			}
			
			$teams = $teamIds;
			shuffle($teams);
			while (count($teams) > 1) {
				$homeTeam = array_pop($teams);
				$guestTeam = array_pop($teams);
				
				// create first round
				$db->queryInsert(array(
						'spieltyp' => $type,
						'pokalname' => $match->cupName,
						'pokalrunde' => $roundInfo['name'],
						'home_verein' => $homeTeam,
						'gast_verein' => $guestTeam,
						'datum' => $roundInfo['firstround_date']
				), $matchTable);
					
				// create second round
				if ($roundInfo['secondround_date']) {
					$db->queryInsert(array(
							'spieltyp' => $type,
							'pokalname' => $match->cupName,
							'pokalrunde' => $roundInfo['name'],
							'home_verein' => $guestTeam,
							'gast_verein' => $homeTeam,
							'datum' => $roundInfo['secondround_date']
					), $matchTable);
				}
			}
			
		}
	}
	
	private static function createMatchForTeamAndRound(WebSoccer $websoccer, DbConnection $db,
			$teamId, $roundId, $firstRoundDate, $secondRoundDate, $cupName, $cupRound) {
		
		// get opponent team from pending list
		$pendingTable = $websoccer->getConfig('db_prefix') . '_cup_round_pending';
		$result = $db->querySelect('team_id', $pendingTable, 'cup_round_id = %d', $roundId, 1);
		$opponent = $result->fetch_array();
		$result->free();
		
		// no opponent -> add to pending list
		if (!$opponent) {
			$db->queryInsert(array('team_id' => $teamId, 'cup_round_id' => $roundId), $pendingTable);
		} else {
			
			$matchTable = $websoccer->getConfig('db_prefix') . '_spiel';
			$type = 'Pokalspiel';
			
			// determine home team of first round (choose randomly)
			if (SimulationHelper::selectItemFromProbabilities(array(1 => 50, 0 => 50))) {
				$homeTeam = $teamId;
				$guestTeam = $opponent['team_id'];
			} else {
				$homeTeam = $opponent['team_id'];
				$guestTeam = $teamId;
			}
			
			// create first round
			$db->queryInsert(array(
					'spieltyp' => $type,
					'pokalname' => $cupName,
					'pokalrunde' => $cupRound,
					'home_verein' => $homeTeam,
					'gast_verein' => $guestTeam,
					'datum' => $firstRoundDate
				), $matchTable);
			
			// create second round
			if ($secondRoundDate) {
				$db->queryInsert(array(
						'spieltyp' => $type,
						'pokalname' => $cupName,
						'pokalrunde' => $cupRound,
						'home_verein' => $guestTeam,
						'gast_verein' => $homeTeam,
						'datum' => $secondRoundDate
				), $matchTable);
			}
			
			// remove opponent team from pending list
			$db->queryDelete($pendingTable, 'team_id = %d AND cup_round_id = %d', array($opponent['team_id'], $roundId));
			
		}
		
	}
	
}

?>
