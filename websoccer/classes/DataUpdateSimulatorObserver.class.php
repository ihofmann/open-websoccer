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
define('POINTS_WIN', 3);
define('POINTS_DRAW', 1);
define('POINTS_LOSS', 0);

/**
 * Updates data base after a match has completed.
 * 
 * @author Ingo Hofmann
 */
class DataUpdateSimulatorObserver implements ISimulatorObserver {
	private $_websoccer;
	private $_db;
	
	private $_teamsWithSoonEndingContracts;
	
	/**
	 * 
	 * @param WebSoccer $websoccer application context
	 * @param DbConnection $db DB connection
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db) {
		$this->_websoccer = $websoccer;
		$this->_db = $db;
		$this->_teamsWithSoonEndingContracts = array();
	}
	
	/**
	 * @see ISimulatorObserver::onBeforeMatchStarts()
	 */
	public function onBeforeMatchStarts(SimulationMatch $match) {
		// compute sold tickets
		if (($this->_websoccer->getConfig('sim_income_trough_friendly') || $match->type !== 'Freundschaft')
				&& !$match->isAtForeignStadium) {
			SimulationAudienceCalculator::computeAndSaveAudience($this->_websoccer, $this->_db, $match);
		}
		
		// update user ids
		$clubTable = $this->_websoccer->getConfig('db_prefix') . '_verein';
		$updateColumns = array();
		
		$result = $this->_db->querySelect('user_id', $clubTable, 'id = %d AND user_id > 0', $match->homeTeam->id);
		$homeUser = $result->fetch_array();
		$result->free();
		if ($homeUser) {
			$updateColumns['home_user_id'] = $homeUser['user_id'];
		}
		
		$result = $this->_db->querySelect('user_id', $clubTable, 'id = %d AND user_id > 0', $match->guestTeam->id);
		$guestUser = $result->fetch_array();
		$result->free();
		if ($guestUser) {
			$updateColumns['gast_user_id'] = $guestUser['user_id'];
		}
		
		if (count($updateColumns)) {
			$this->_db->queryUpdate($updateColumns, $this->_websoccer->getConfig('db_prefix') . '_spiel', 
					'id = %d', $match->id);
		}
	}
	
	/**
	 * @see ISimulatorObserver::onMatchCompleted()
	 */
	public function onMatchCompleted(SimulationMatch $match) {
		
		// players
		$isFriendlyMatch = ($match->type == 'Freundschaft');
		
		if ($isFriendlyMatch) {
			$this->updatePlayersOfFriendlymatch($match->homeTeam);
			$this->updatePlayersOfFriendlymatch($match->guestTeam);
		} else {
			// player statistics and salary
			$isTie = $match->homeTeam->getGoals() == $match->guestTeam->getGoals();
			$this->updatePlayers($match, $match->homeTeam, $match->homeTeam->getGoals() > $match->guestTeam->getGoals(), $isTie);
			$this->updatePlayers($match, $match->guestTeam, $match->homeTeam->getGoals() < $match->guestTeam->getGoals(), $isTie);
			
			// sponsor
			if (!$match->homeTeam->isNationalTeam) {
				$this->creditSponsorPayments($match->homeTeam, TRUE, $match->homeTeam->getGoals() > $match->guestTeam->getGoals());
			}
			if (!$match->guestTeam->isNationalTeam) {
				$this->creditSponsorPayments($match->guestTeam, FALSE, $match->homeTeam->getGoals() < $match->guestTeam->getGoals());
			}
			
			// points and statistics
			if ($match->type == 'Ligaspiel') {
				$this->updateTeams($match);
			} else if (strlen($match->cupRoundGroup)) {
				$this->updateTeamsOfCupGroupMatch($match);
				SimulationCupMatchHelper::checkIfMatchIsLastMatchOfGroupRoundAndCreateFollowingMatches($this->_websoccer, $this->_db, $match);
			}
			
			// highscore and fan popularity
			$this->updateUsers($match);
		}
		
		// delete formations
		$this->_db->queryDelete($this->_websoccer->getConfig('db_prefix') . '_aufstellung', 'match_id = %d', $match->id);
	}
	
	/**
	 * only update freshness, stamina and injury on friendly matches
	 */
	private function updatePlayersOfFriendlymatch(SimulationTeam $team) {
		if (!count($team->positionsAndPlayers)) {
			return;
		}
		
		foreach ($team->positionsAndPlayers as $position => $players) {
			foreach ($players as $player) {
				$this->updatePlayerOfFriendlyMatch($player);
			}
		}
		
		if (is_array($team->removedPlayers) && count($team->removedPlayers)) {
			foreach ($team->removedPlayers as $player) {
				$this->updatePlayerOfFriendlyMatch($player);
			}
		}
	}
	
	private function updatePlayerOfFriendlyMatch(SimulationPlayer $player) {
		$columns = array();
		
		// freshness and stamina
		if ($this->_websoccer->getConfig('sim_tiredness_through_friendly')) {
			$columns['w_frische'] = $player->strengthFreshness;
			
			$minMinutes = (int) $this->_websoccer->getConfig('sim_played_min_minutes');
			$staminaChange = (int) $this->_websoccer->getConfig('sim_strengthchange_stamina');
			
			if ($player->getMinutesPlayed() >= $minMinutes) {
				$columns['w_kondition'] = min(100, $player->strengthStamina + $staminaChange);
			}
		}
		
		// injury
		if ($player->injured > 0 && $this->_websoccer->getConfig('sim_injured_after_friendly')) {
			$columns['verletzt'] = $player->injured;
		}
		
		// update if any changes
		if (count($columns)) {
			$fromTable = $this->_websoccer->getConfig('db_prefix') . '_spieler';
			$this->_db->queryUpdate($columns, $fromTable, 'id = %d', $player->id);
		}
	}
	
	private function updatePlayers(SimulationMatch $match, SimulationTeam $team, $isTeamWinner, $isTie) {
		$playersOnPitch = array();
		
		foreach ($team->positionsAndPlayers as $position => $players) {
			foreach ($players as $player) {
				$playersOnPitch[$player->id] = $player;
			}
		}
		
		if (is_array($team->removedPlayers) && count($team->removedPlayers)) {
			foreach ($team->removedPlayers as $player) {
				$playersOnPitch[$player->id] = $player;
			}
		}
		
		// compute salary payment.
		$totalSalary = 0;
		
		$pcolumns = 'id,vorname,nachname,kunstname,verein_id,vertrag_spiele,vertrag_gehalt,vertrag_torpraemie,w_zufriedenheit,w_frische,verletzt,gesperrt,gesperrt_cups,gesperrt_nationalteam,lending_fee,lending_matches,lending_owner_id';
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_spieler';
		
		if ($team->isNationalTeam) {
			$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_nationalplayer AS NP ON NP.player_id = id';
			$whereCondition = 'NP.team_id = %d AND status = 1';
		} else {
			$whereCondition = 'verein_id = %d AND status = 1';
		}
		
		$parameters = $team->id;
		
		$result = $this->_db->querySelect($pcolumns, $fromTable, $whereCondition, $parameters);
		while ($playerinfo = $result->fetch_array()) {
			
			$totalSalary += $playerinfo['vertrag_gehalt'];
			
			// add goal bonus
			if (isset($playersOnPitch[$playerinfo['id']])) {
				$player = $playersOnPitch[$playerinfo['id']];
				if ($player->getGoals()) {
					$totalSalary += $player->getGoals() * $playerinfo['vertrag_torpraemie'];
				}
				
				// update player who did not play at all
			} else {
				$this->updatePlayerWhoDidNotPlay($match, $team->isNationalTeam, $playerinfo);
			}
			
		}
		$result->free();
		
		if (!$team->isNationalTeam) {
			$this->deductSalary($team, $totalSalary);
		}
		
		// update players who actually played
		foreach ($playersOnPitch as $player) {
			$this->updatePlayer($match, $player, $isTeamWinner, $isTie);
		}
	}
	
	private function updatePlayer(SimulationMatch $match, SimulationPlayer $player, $isTeamWinner, $isTie) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_spieler';
		$whereCondition = 'id = %d';
		$parameters = $player->id;
		
		$minMinutes = (int) $this->_websoccer->getConfig('sim_played_min_minutes');
		$blockYellowCards = (int) $this->_websoccer->getConfig('sim_block_player_after_yellowcards');
		$staminaChange = (int) $this->_websoccer->getConfig('sim_strengthchange_stamina');
		$satisfactionChange = (int) $this->_websoccer->getConfig('sim_strengthchange_satisfaction');
		
		if ($player->team->isNationalTeam) {
			$columns['gesperrt_nationalteam'] = $player->blocked;
		} elseif ($match->type == 'Pokalspiel') {
			$columns['gesperrt_cups'] = $player->blocked;
		} else {
			$columns['gesperrt'] = $player->blocked;
		}
		
		// get previous player statistics and lending info
		$pcolumns = 'id,vorname,nachname,kunstname,verein_id,vertrag_spiele,st_tore,st_assists,st_spiele,st_karten_gelb,st_karten_gelb_rot,st_karten_rot,sa_tore,sa_assists,sa_spiele,sa_karten_gelb,sa_karten_gelb_rot,sa_karten_rot,lending_fee,lending_owner_id,lending_matches';
		$result = $this->_db->querySelect($pcolumns, $fromTable, $whereCondition, $parameters);
		$playerinfo = $result->fetch_array();
		$result->free();
		
		// update statistic
		$columns['st_tore'] = $playerinfo['st_tore'] + $player->getGoals();
		$columns['sa_tore'] = $playerinfo['sa_tore'] + $player->getGoals();
		
		$columns['st_assists'] = $playerinfo['st_assists'] + $player->getAssists();
		$columns['sa_assists'] = $playerinfo['sa_assists'] + $player->getAssists();
		
		$columns['st_spiele'] = $playerinfo['st_spiele'] + 1;
		$columns['sa_spiele'] = $playerinfo['sa_spiele'] + 1;
		
		if ($player->redCard) {
			$columns['st_karten_rot'] = $playerinfo['st_karten_rot'] + 1;
			$columns['sa_karten_rot'] = $playerinfo['sa_karten_rot'] + 1;
		} else if ($player->yellowCards) {
			
			if ($player->yellowCards == 2) {
				$columns['st_karten_gelb_rot'] = $playerinfo['st_karten_gelb_rot'] + 1;
				$columns['sa_karten_gelb_rot'] = $playerinfo['sa_karten_gelb_rot'] + 1;
				
				if ($player->team->isNationalTeam) {
					$columns['gesperrt_nationalteam'] = '1';
				} elseif ($match->type == 'Pokalspiel') {
					$columns['gesperrt_cups'] = '1';
				} else {
					$columns['gesperrt'] = '1';
				}
			} elseif (!$player->team->isNationalTeam) {
				$columns['st_karten_gelb'] = $playerinfo['st_karten_gelb'] + 1;
				$columns['sa_karten_gelb'] = $playerinfo['sa_karten_gelb'] + 1;
				
				// block after certain number of matches ('Gelbsperre')
				if ($match->type == 'Ligaspiel' && $blockYellowCards > 0 && $columns['sa_karten_gelb'] % $blockYellowCards == 0) {
					$columns['gesperrt'] = 1;
				}
			}
		}
		
		if (!$player->team->isNationalTeam) {
			$columns['vertrag_spiele'] = max(0, $playerinfo['vertrag_spiele'] - 1);
			if ($columns['vertrag_spiele'] == 5) {
				$this->_teamsWithSoonEndingContracts[$player->team->id] = TRUE;
			}
		}
		
		// update other fields
		if (!$player->team->isNationalTeam || $this->_websoccer->getConfig('sim_playerupdate_through_nationalteam')) {
			$columns['w_frische'] = $player->strengthFreshness;
			$columns['verletzt'] = $player->injured;
			
			if ($player->getMinutesPlayed() >= $minMinutes) {
				$columns['w_kondition'] = min(100, $player->strengthStamina + $staminaChange);
				$columns['w_zufriedenheit'] = min(100, $player->strengthSatisfaction + $satisfactionChange);
			} else {
				$columns['w_kondition'] = max(1, $player->strengthStamina - $staminaChange);
				$columns['w_zufriedenheit'] = max(1, $player->strengthSatisfaction - $satisfactionChange);
			}
			
			// result dependent satisfaction change
			if (!$isTie) {
				if ($isTeamWinner) {
					$columns['w_zufriedenheit'] = min(100, $columns['w_zufriedenheit'] + $satisfactionChange);
				} else {
					$columns['w_zufriedenheit'] = max(1, $columns['w_zufriedenheit'] - $satisfactionChange);
				}
			}
			
		}
		
		if (!$player->team->isNationalTeam && $playerinfo['lending_matches'] > 0) {
			$this->handleBorrowedPlayer($columns, $playerinfo);
		}
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
	private function updatePlayerWhoDidNotPlay(SimulationMatch $match, $isNationalTeam, $playerinfo) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_spieler';
		$whereCondition = 'id = %d';
		$parameters = $playerinfo['id'];
		$satisfactionChange = (int) $this->_websoccer->getConfig('sim_strengthchange_satisfaction');
		
		if ($isNationalTeam) {
			$columns['gesperrt_nationalteam'] = max(0, $playerinfo['gesperrt_nationalteam'] - 1);
		} elseif ($match->type == 'Pokalspiel') {
			$columns['gesperrt_cups'] = max(0, $playerinfo['gesperrt_cups'] - 1);
		} else {
			$columns['gesperrt'] = max(0, $playerinfo['gesperrt'] - 1);
		}
		
		$columns['verletzt'] = max(0, $playerinfo['verletzt'] - 1);
		if (!$isNationalTeam) {
			$columns['vertrag_spiele'] = max(0, $playerinfo['vertrag_spiele'] - 1);
			if ($columns['vertrag_spiele'] == 5) {
				$this->_teamsWithSoonEndingContracts[$playerinfo['id']] = TRUE;
			}
		}
		
		if (!$isNationalTeam || $this->_websoccer->getConfig('sim_playerupdate_through_nationalteam')) {
			$columns['w_zufriedenheit'] = max(1, $playerinfo['w_zufriedenheit'] - $satisfactionChange);
			$columns['w_frische'] = min(100, $playerinfo['w_frische'] + $this->_websoccer->getConfig('sim_strengthchange_freshness_notplayed'));
		}
		
		if (!$isNationalTeam && $playerinfo['lending_matches'] > 0) {
			$this->handleBorrowedPlayer($columns, $playerinfo);
		}
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
	private function deductSalary(SimulationTeam $team, $salary) {
		
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $team->id,
			$salary,
			'match_salarypayment_subject',
			'match_salarypayment_sender');
		
	}
	
	private function updateTeams(SimulationMatch $match) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_verein';
		$whereCondition = 'id = %d';
		
		$tcolumns = 'st_tore,st_gegentore,st_spiele,st_siege,st_niederlagen,st_unentschieden,st_punkte,sa_tore,sa_gegentore,sa_spiele,sa_siege,sa_niederlagen,sa_unentschieden,sa_punkte';
		
		$result = $this->_db->querySelect($tcolumns, $fromTable, $whereCondition, $match->homeTeam->id);
		$home = $result->fetch_array();
		$result->free();
		
		$result = $this->_db->querySelect($tcolumns, $fromTable, $whereCondition, $match->guestTeam->id);
		$guest = $result->fetch_array();
		$result->free();
		
		// update statistic
		$homeColumns['sa_spiele'] = $home['sa_spiele'] + 1;
		$homeColumns['st_spiele'] = $home['st_spiele'] + 1;
		
		$homeColumns['sa_tore'] = $home['sa_tore'] + $match->homeTeam->getGoals();
		$homeColumns['st_tore'] = $home['st_tore'] + $match->homeTeam->getGoals();
		
		$homeColumns['sa_gegentore'] = $home['sa_gegentore'] + $match->guestTeam->getGoals();
		$homeColumns['st_gegentore'] = $home['st_gegentore'] + $match->guestTeam->getGoals();
		
		$guestColumns['sa_spiele'] = $guest['sa_spiele'] + 1;
		$guestColumns['st_spiele'] = $guest['st_spiele'] + 1;
		
		$guestColumns['sa_tore'] = $guest['sa_tore'] + $match->guestTeam->getGoals();
		$guestColumns['st_tore'] = $guest['st_tore'] + $match->guestTeam->getGoals();
		
		$guestColumns['sa_gegentore'] = $guest['sa_gegentore'] + $match->homeTeam->getGoals();
		$guestColumns['st_gegentore'] = $guest['st_gegentore'] + $match->homeTeam->getGoals();
		
		// assign points
		if ($match->homeTeam->getGoals() > $match->guestTeam->getGoals()) {
			$homeColumns['sa_siege'] = $home['sa_siege'] + 1;
			$homeColumns['st_siege'] = $home['st_siege'] + 1;
			
			$homeColumns['sa_punkte'] = $home['sa_punkte'] + POINTS_WIN;
			$homeColumns['st_punkte'] = $home['st_punkte'] + POINTS_WIN;
			
			$guestColumns['sa_niederlagen'] = $guest['sa_niederlagen'] + 1;
			$guestColumns['st_niederlagen'] = $guest['st_niederlagen'] + 1;
			
			$guestColumns['sa_punkte'] = $guest['sa_punkte'] + POINTS_LOSS;
			$guestColumns['st_punkte'] = $guest['st_punkte'] + POINTS_LOSS;
		} else if ($match->homeTeam->getGoals() == $match->guestTeam->getGoals()) {
			$homeColumns['sa_unentschieden'] = $home['sa_unentschieden'] + 1;
			$homeColumns['st_unentschieden'] = $home['st_unentschieden'] + 1;
			
			$homeColumns['sa_punkte'] = $home['sa_punkte'] + POINTS_DRAW;
			$homeColumns['st_punkte'] = $home['st_punkte'] + POINTS_DRAW;
			
			$guestColumns['sa_unentschieden'] = $guest['sa_unentschieden'] + 1;
			$guestColumns['st_unentschieden'] = $guest['st_unentschieden'] + 1;
			
			$guestColumns['sa_punkte'] = $guest['sa_punkte'] + POINTS_DRAW;
			$guestColumns['st_punkte'] = $guest['st_punkte'] + POINTS_DRAW;
		} else {
			$homeColumns['sa_niederlagen'] = $home['sa_niederlagen'] + 1;
			$homeColumns['st_niederlagen'] = $home['st_niederlagen'] + 1;
			
			$homeColumns['sa_punkte'] = $home['sa_punkte'] + POINTS_LOSS;
			$homeColumns['st_punkte'] = $home['st_punkte'] + POINTS_LOSS;
			
			$guestColumns['sa_siege'] = $guest['sa_siege'] + 1;
			$guestColumns['st_siege'] = $guest['st_siege'] + 1;
			
			$guestColumns['sa_punkte'] = $guest['sa_punkte'] + POINTS_WIN;
			$guestColumns['st_punkte'] = $guest['st_punkte'] + POINTS_WIN;
		}
		
		$this->_db->queryUpdate($homeColumns, $fromTable, $whereCondition, $match->homeTeam->id);
		$this->_db->queryUpdate($guestColumns, $fromTable, $whereCondition, $match->guestTeam->id);
	}
	
	private function updateTeamsOfCupGroupMatch(SimulationMatch $match) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_cup_round_group AS G';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_cup_round AS R ON R.id = G.cup_round_id';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_cup AS C ON C.id = R.cup_id';
		
		$whereCondition = 'C.name = \'%s\' AND R.name = \'%s\' AND G.name = \'%s\' AND G.team_id = %d';
		
		$tcolumns = array(
				'G.tab_points' => 'tab_points',
				'G.tab_goals' => 'tab_goals',
				'G.tab_goalsreceived' => 'tab_goalsreceived',
				'G.tab_wins' => 'tab_wins',
				'G.tab_draws' => 'tab_draws',
				'G.tab_losses' => 'tab_losses'
				);
		
		$homeParameters = array($match->cupName, $match->cupRoundName, $match->cupRoundGroup, $match->homeTeam->id);
		$result = $this->_db->querySelect($tcolumns, $fromTable, $whereCondition, $homeParameters, 1);
		$home = $result->fetch_array();
		$result->free();
		
		$guestParameters = array($match->cupName, $match->cupRoundName, $match->cupRoundGroup, $match->guestTeam->id);
		$result = $this->_db->querySelect($tcolumns, $fromTable, $whereCondition, $guestParameters, 1);
		$guest = $result->fetch_array();
		$result->free();
		
		// update statistic
		$homeColumns['tab_goals'] = $home['tab_goals'] + $match->homeTeam->getGoals();
		$homeColumns['tab_goalsreceived'] = $home['tab_goalsreceived'] + $match->guestTeam->getGoals();
		
		$guestColumns['tab_goals'] = $guest['tab_goals'] + $match->guestTeam->getGoals();
		$guestColumns['tab_goalsreceived'] = $guest['tab_goalsreceived'] + $match->homeTeam->getGoals();
		
		// assign points
		if ($match->homeTeam->getGoals() > $match->guestTeam->getGoals()) {
			$homeColumns['tab_wins'] = $home['tab_wins'] + 1;
				
			$homeColumns['tab_points'] = $home['tab_points'] + POINTS_WIN;
				
			$guestColumns['tab_losses'] = $guest['tab_losses'] + 1;
				
			$guestColumns['tab_points'] = $guest['tab_points'] + POINTS_LOSS;
		} else if ($match->homeTeam->getGoals() == $match->guestTeam->getGoals()) {
			$homeColumns['tab_draws'] = $home['tab_draws'] + 1;
				
			$homeColumns['tab_points'] = $home['tab_points'] + POINTS_DRAW;
				
			$guestColumns['tab_draws'] = $guest['tab_draws'] + 1;
				
			$guestColumns['tab_points'] = $guest['tab_points'] + POINTS_DRAW;
		} else {
			$homeColumns['tab_losses'] = $home['tab_losses'] + 1;
				
			$homeColumns['tab_points'] = $home['tab_points'] + POINTS_LOSS;
				
			$guestColumns['tab_wins'] = $guest['tab_wins'] + 1;
				
			$guestColumns['tab_points'] = $guest['tab_points'] + POINTS_WIN;
		}
		
		$this->_db->queryUpdate($homeColumns, $fromTable, $whereCondition, $homeParameters);
		$this->_db->queryUpdate($guestColumns, $fromTable, $whereCondition, $guestParameters);
	}
	
	private function creditSponsorPayments(SimulationTeam $team, $isHomeTeam, $teamIsWinner) {
		
		$columns = 'S.name AS sponsor_name, b_spiel,b_heimzuschlag,b_sieg,T.sponsor_spiele AS sponsor_matches';
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_verein AS T';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_sponsor AS S ON S.id = T.sponsor_id';
		$whereCondition = 'T.id = %d AND T.sponsor_spiele > 0';
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $team->id);
		$sponsor = $result->fetch_array();
		$result->free();
		
		if (isset($sponsor['sponsor_matches'])) {
			$amount = $sponsor['b_spiel'];
			
			if ($isHomeTeam) {
				$amount += $sponsor['b_heimzuschlag'];
			}
			
			if ($teamIsWinner) {
				$amount += $sponsor['b_sieg'];
			}
			
			BankAccountDataService::creditAmount($this->_websoccer, $this->_db, $team->id,
				$amount,
				'match_sponsorpayment_subject',
				$sponsor['sponsor_name']);
			
			// update sponsor contract
			$updatecolums['sponsor_spiele'] = max(0, $sponsor['sponsor_matches'] - 1);
			if ($updatecolums['sponsor_spiele'] == 0) {
				$updatecolums['sponsor_id'] = '';
			}
			$whereCondition = 'id = %d';
			$fromTable = $this->_websoccer->getConfig('db_prefix') . '_verein';
			$this->_db->queryUpdate($updatecolums, $fromTable, $whereCondition, $team->id);
		}
		
	}
	
	private function updateUsers(SimulationMatch $match) {
		$highscoreWin = $this->_websoccer->getConfig('highscore_win');
		$highscoreLoss = $this->_websoccer->getConfig('highscore_loss');
		$highscoreDraw = $this->_websoccer->getConfig('highscore_draw');
		
		$columns = 'U.id AS u_id, U.highscore AS highscore, U.fanbeliebtheit AS popularity';
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_verein AS T';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_user AS U ON U.id = T.user_id';
		$whereCondition = 'T.id = %d';
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $match->homeTeam->id);
		$homeUser = $result->fetch_array();
		$result->free();
		
		$updateTable = $this->_websoccer->getConfig('db_prefix') . '_user';
		$updateCondition = 'id = %d';
		
		// make popularity dependent on strength
		$homeStrength = $match->homeTeam->computeTotalStrength($this->_websoccer, $match);		
		$guestStrength = $match->guestTeam->computeTotalStrength($this->_websoccer, $match);
		
		// the strength difference between home and guest team in per cent. Positive value means, home team is stronger.
		if ($homeStrength) {
			$homeGuestStrengthDiff = round(($homeStrength - $guestStrength) / $homeStrength * 100);
		} else {
			$homeGuestStrengthDiff = 0;
		}
		
		// update user of home team
		if (!empty($homeUser['u_id']) && !$match->homeTeam->noFormationSet) {
			if ($match->homeTeam->getGoals() > $match->guestTeam->getGoals()) {
				$homeColumns['highscore'] = max(0, $homeUser['highscore'] + $highscoreWin);
				
				// fans only get excited if team was not much stronger
				$popFactor = 1.1;
				if ($homeGuestStrengthDiff >= 20) {
					$popFactor = 1.05;
				}
				
				$homeColumns['fanbeliebtheit'] = min(100, round($homeUser['popularity'] * $popFactor));
				
				// badge applicable?
				$goalsDiff = $match->homeTeam->getGoals() - $match->guestTeam->getGoals();
				BadgesDataService::awardBadgeIfApplicable($this->_websoccer, $this->_db, $homeUser['u_id'], 'win_with_x_goals_difference', $goalsDiff);
			} else if ($match->homeTeam->getGoals() == $match->guestTeam->getGoals()) {
				$homeColumns['highscore'] = max(0, $homeUser['highscore'] + $highscoreDraw);
				
				// fans react on strength difference.
				$popFactor = 1.0;
				if ($homeGuestStrengthDiff >= 20) {
					// if much stronger, they dislike it
					$popFactor = 0.95;
				} else if ($homeGuestStrengthDiff <= -20) {
					// if much weaker, they like it! it is an achievement
					$popFactor = 1.05;
				}
				
				$homeColumns['fanbeliebtheit'] = min(100, round($homeUser['popularity'] * $popFactor));
			} else {
				$homeColumns['highscore'] = max(0, $homeUser['highscore'] + $highscoreLoss);
				
				// fans react on strength difference.
				$popFactor = 0.95;
				if ($homeGuestStrengthDiff >= 20) {
					// if much stronger, they dislike it even more
					$popFactor = 0.90;
				} else if ($homeGuestStrengthDiff <= -20) {
					// if much weaker, it is ok for them
					$popFactor = 1.00;
				}
				$homeColumns['fanbeliebtheit'] = max(1, round($homeUser['popularity'] * $popFactor));
			}
			
			if (!$match->homeTeam->isManagedByInterimManager) {
				$this->_db->queryUpdate($homeColumns, $updateTable, $updateCondition, $homeUser['u_id']);
			}
			
			// send notification about soon ending contracts
			if (isset($this->_teamsWithSoonEndingContracts[$match->homeTeam->id])) {
				$this->notifyAboutSoonEndingContracts($homeUser['u_id'], $match->homeTeam->id);
			}
		}
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $match->guestTeam->id);
		$guestUser = $result->fetch_array();
		$result->free();
		
		if (!empty($guestUser['u_id']) && !$match->guestTeam->noFormationSet) {
			if ($match->guestTeam->getGoals() > $match->homeTeam->getGoals()) {
				// fans only get excited if team was not much stronger
				$popFactor = 1.1;
				if ($homeGuestStrengthDiff <= -20) {
					$popFactor = 1.05;
				}
				
				$guestColumns['highscore'] = max(0, $guestUser['highscore'] + $highscoreWin);
				$guestColumns['fanbeliebtheit'] = min(100, round($guestUser['popularity'] * $popFactor));
				
				// badge applicable?
				$goalsDiff = $match->guestTeam->getGoals() - $match->homeTeam->getGoals();
				BadgesDataService::awardBadgeIfApplicable($this->_websoccer, $this->_db, $guestUser['u_id'], 'win_with_x_goals_difference', $goalsDiff);
			} else if ($match->guestTeam->getGoals() == $match->homeTeam->getGoals()) {
				// fans react on strength difference.
				$popFactor = 1.0;
				if ($homeGuestStrengthDiff <= -20) {
					// if much stronger, they dislike it
					$popFactor = 0.95;
				} else if ($homeGuestStrengthDiff >= 20) {
					// if much weaker, they like it! it is an achievement
					$popFactor = 1.05;
				}
				
				$guestColumns['highscore'] = max(0, $guestUser['highscore'] + $highscoreDraw);
				$guestColumns['fanbeliebtheit'] = min(100, round($guestUser['popularity'] * $popFactor));
			} else {
				$guestColumns['highscore'] = max(0, $guestUser['highscore'] + $highscoreLoss);
				
				// fans react on strength difference.
				$popFactor = 0.95;
				if ($homeGuestStrengthDiff <= -20) {
					// if much stronger, they dislike it even more
					$popFactor = 0.90;
				} else if ($homeGuestStrengthDiff >= 20) {
					// if much weaker, it is ok for them
					$popFactor = 1.00;
				}
				$guestColumns['fanbeliebtheit'] = max(1, round($guestUser['popularity'] * $popFactor));
			}
				
			if (!$match->guestTeam->isManagedByInterimManager) {
				$this->_db->queryUpdate($guestColumns, $updateTable, $updateCondition, $guestUser['u_id']);
			}
			
			// send notification about soon ending contracts
			if (isset($this->_teamsWithSoonEndingContracts[$match->guestTeam->id])) {
				$this->notifyAboutSoonEndingContracts($guestUser['u_id'], $match->guestTeam->id);
			}
		}
	}
	
	// updates $columns and expects that player changes get saved afterwards.
	private function handleBorrowedPlayer(&$columns, $playerinfo) {
		$columns['lending_matches'] = max(0, $playerinfo['lending_matches'] - 1);
		
		// move back to original team
		if ($columns['lending_matches'] == 0) {
			$columns['lending_fee'] = 0;
			$columns['lending_owner_id'] = 0;
			$columns['verein_id'] = $playerinfo['lending_owner_id'];
			
			
			// get manager IDs in order to send notification
			$borrower = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $playerinfo['verein_id']);
			$lender = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $playerinfo['lending_owner_id']);
			
			// create notifications
			$messageKey = 'lending_notification_return';
			$messageType = 'lending_return';
			$playerName = ($playerinfo['kunstname']) ? $playerinfo['kunstname'] : $playerinfo['vorname'] . ' ' . $playerinfo['nachname'];
			$messageData = array('player' => $playerName, 'borrower' => $borrower['team_name'], 'lender' => $lender['team_name']);
			
			if ($borrower['user_id']) {
				NotificationsDataService::createNotification($this->_websoccer, $this->_db, $borrower['user_id'], 
					$messageKey, $messageData, $messageType, 'player', 'id=' . $playerinfo['id']);
			}
			
			if ($lender['user_id']) {
				NotificationsDataService::createNotification($this->_websoccer, $this->_db, $lender['user_id'],
					$messageKey, $messageData, $messageType, 'player', 'id=' . $playerinfo['id']);
			}
			
		}
	}
	
	private function notifyAboutSoonEndingContracts($userId, $teamId) {
		
		NotificationsDataService::createNotification($this->_websoccer, $this->_db, $userId, 'notification_soon_ending_playercontracts',
			'', 'soon_ending_playercontracts', 'myteam', null, $teamId);
		
		unset($this->_teamsWithSoonEndingContracts[$teamId]);
	}
	
	/**
	 * Empty implementation since there are no data to update.
	 * 
	 * @see ISimulatorObserver::onSubstitution()
	 */
	public function onSubstitution(SimulationMatch $match, SimulationSubstitution $substitution) {
		// nothing to do here, just be compliant with API...
	}
	
}
?>