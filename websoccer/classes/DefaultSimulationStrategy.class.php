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
 * The default simulation strategy simulates actions by probabilities, depending on the teams' tactic and players' strength.
 * 
 * @author Ingo Hofmann
 */
class DefaultSimulationStrategy implements ISimulationStrategy {
	
	private $_websoccer;
	private $_passTargetProbPerPosition;
	private $_opponentPositions;
	private $_shootStrengthPerPosition;
	private $_shootProbPerPosition;
	private $_observers;
	
	/**
	 * @param WebSoccer $websoccer context.
	 */
	public function __construct(WebSoccer $websoccer) {
		$this->_websoccer = $websoccer;
		
		$this->_setPassTargetProbabilities();
		
		$this->_setOpponentPositions();
		
		$this->_setShootStrengthPerPosition();
		
		$this->_setShootProbPerPosition();
		
		$this->_observers = array();
	}
	
	/**
	 * Attaches event oberservers which will be called on appropriate events.
	 * 
	 * @param ISimulationObserver $observer observer instance.
	 */
	public function attachObserver(ISimulationObserver $observer) {
		$this->_observers[] = $observer;
	}
	
	/**
	 * @see ISimulationStrategy::kickoff()
	 */
	public function kickoff(SimulationMatch $match) {
		$pHomeTeam[TRUE] = 50;
		$pHomeTeam[FALSE]  = 50;
		
		$team = SimulationHelper::selectItemFromProbabilities($pHomeTeam) ? $match->homeTeam : $match->guestTeam;
		
		$match->setPlayerWithBall(SimulationHelper::selectPlayer($team, PLAYER_POSITION_DEFENCE, null));
	}

	/**
	 * @see ISimulationStrategy::nextAction()
	 */
	public function nextAction(SimulationMatch $match) {
		$player = $match->getPlayerWithBall();
		
		// goalies can only pass the ball
		if ($player->position == PLAYER_POSITION_GOALY) {
			return 'passBall';
		}
		
		// Probability of attack depends on opponent's formation
		$opponentTeam = SimulationHelper::getOpponentTeam($player, $match);
		$opponentPosition = $this->_opponentPositions[$player->position];
		
		$noOfOwnPlayersInPosition = count($player->team->positionsAndPlayers[$player->position]);
		
		if (isset($opponentTeam->positionsAndPlayers[$opponentPosition])) {
			$noOfOpponentPlayersInPosition = count($opponentTeam->positionsAndPlayers[$opponentPosition]);
		} else {
			$noOfOpponentPlayersInPosition = 0;
		}
		
		$pTackle = 10;
		if ($noOfOpponentPlayersInPosition == $noOfOwnPlayersInPosition) {
			$pTackle += 10;
		} else if ($noOfOpponentPlayersInPosition > $noOfOwnPlayersInPosition) {
			$pTackle += 10 + 20 * ($noOfOpponentPlayersInPosition - $noOfOwnPlayersInPosition);
		}
		$pAction['tackle'] = min($pTackle, 40);
		
		// probability of shooting depends on position + tactic
		$pShoot = $this->_shootProbPerPosition[$player->position];
		$tacticInfluence = ($this->_getOffensiveStrength($player->team, $match) - $this->_getDefensiveStrength($opponentTeam, $match)) / 10;
		
		// reduce number of attempts if own team focussed on counterattacks
		if ($player->team->counterattacks) {
			$pShoot = round($pShoot * 0.5);
		}
		
		// also consider current result
		$resultInfluence = ($player->team->getGoals() - $opponentTeam->getGoals()) * (0 - 5);
		
		// if team is in deficit, the morale can push up to additional 5%
		if ($player->team->getGoals() < $opponentTeam->getGoals() && $player->team->morale) {
			$resultInfluence += floor($player->team->morale / 100 * 5);
		}
		
		// forwards/midfielders have a minimum shoot probability of 5%
		if ($player->position == PLAYER_POSITION_STRIKER || $player->position == PLAYER_POSITION_MIDFIELD) {
			$minShootProb = 5;
		} else {
			$minShootProb = 1;
		}
		
		$pAction['shoot'] = round(max($minShootProb, min($pShoot + $tacticInfluence + $resultInfluence, 50)) * $this->_websoccer->getConfig('sim_shootprobability') / 100);
		
		$pAction['passBall'] = 100 - $pAction['tackle'] - $pAction['shoot'] ;
		
		return SimulationHelper::selectItemFromProbabilities($pAction);
	}

	/**
	 * @see ISimulationStrategy::passBall()
	 */
	public function passBall(SimulationMatch $match) {
		$player = $match->getPlayerWithBall();
		
		// failed to pass the ball?
		$pFailed[FALSE] = round(($player->getTotalStrength($this->_websoccer, $match) + $player->strengthTech) / 2);
		
		// probability of failure increases if long passes are activated
		if ($player->team->longPasses) {
			$pFailed[FALSE] = round($pFailed[FALSE] * 0.7);
		}
		
		$pFailed[TRUE] = 100 - $pFailed[FALSE];
		if (SimulationHelper::selectItemFromProbabilities($pFailed) == TRUE) {
			$opponentTeam = SimulationHelper::getOpponentTeam($player, $match);
			$targetPosition = $this->_opponentPositions[$player->position];
			$match->setPlayerWithBall(SimulationHelper::selectPlayer($opponentTeam, $targetPosition, null));
			
			foreach ($this->_observers as $observer) {
				$observer->onBallPassFailure($match, $player);
			}
			
			return FALSE;
		}
		
		// compute probabilities for target position
		$pTarget[PLAYER_POSITION_GOALY] = $this->_passTargetProbPerPosition[$player->position][PLAYER_POSITION_GOALY];
		$pTarget[PLAYER_POSITION_DEFENCE] = $this->_passTargetProbPerPosition[$player->position][PLAYER_POSITION_DEFENCE];
		$pTarget[PLAYER_POSITION_STRIKER] = $this->_passTargetProbPerPosition[$player->position][PLAYER_POSITION_STRIKER];
		
		// consider tactic option: long passes
		if ($player->position != PLAYER_POSITION_GOALY) {
			$pTarget[PLAYER_POSITION_STRIKER] += 10;
		}
		
		$offensiveInfluence = round(10 - $player->team->offensive * 0.2);
		$pTarget[PLAYER_POSITION_DEFENCE] = $pTarget[PLAYER_POSITION_DEFENCE] + $offensiveInfluence;
		
		$pTarget[PLAYER_POSITION_MIDFIELD] = 100 - $pTarget[PLAYER_POSITION_STRIKER] - $pTarget[PLAYER_POSITION_DEFENCE] - $pTarget[PLAYER_POSITION_GOALY];
		
		// select target position
		$targetPosition = SimulationHelper::selectItemFromProbabilities($pTarget);
		
		// select player
		$match->setPlayerWithBall(SimulationHelper::selectPlayer($player->team, $targetPosition, $player));
		
		foreach ($this->_observers as $observer) {
			$observer->onBallPassSuccess($match, $player);
		}
		return TRUE;
	}

	/**
	 * Computes a tackle between the player with ball and an opponent player who will be picked by this implementation.
	 * Also triggers yellow/red cards, as well as injuries and penalties.
	 * 
	 * @see ISimulationStrategy::tackle()
	 */
	public function tackle(SimulationMatch $match) {
		$player = $match->getPlayerWithBall();
		
		$opponentTeam = SimulationHelper::getOpponentTeam($player, $match);
		$targetPosition = $this->_opponentPositions[$player->position];
		$opponent = SimulationHelper::selectPlayer($opponentTeam, $targetPosition, null);
		
		// can win?
		$pWin[TRUE] = max(1, min(50 + $player->getTotalStrength($this->_websoccer, $match) - $opponent->getTotalStrength($this->_websoccer, $match), 99));
		$pWin[FALSE] = 100 - $pWin[TRUE];
		
		$result = SimulationHelper::selectItemFromProbabilities($pWin);
		
		foreach ($this->_observers as $observer) {
			$observer->onAfterTackle($match, ($result) ? $player : $opponent, ($result) ? $opponent : $player);
		}
		
		// player can keep the ball.
		if ($result == TRUE) {
			// opponent: yellow / redcard
			$pTackle['yellow'] = round(max(1, min(20, round((100 - $opponent->strengthTech) / 2))) * $this->_websoccer->getConfig('sim_cardsprobability') / 100);
			
			// prevent too many yellow-red cards
			if ($opponent->yellowCards > 0) {
				$pTackle['yellow'] = round($pTackle['yellow'] / 2);
			}
			
			$pTackle['red'] = 1;
			// if chances for yellow card is very high, then also chances for red card increased
			if ($pTackle['yellow']  > 15) {
				$pTackle['red'] = 3;
			}
			
			$pTackle['fair'] = 100 - $pTackle['yellow'] - $pTackle['red'];
			
			$tackled = SimulationHelper::selectItemFromProbabilities($pTackle);
			if ($tackled == 'yellow' || $tackled == 'red') {
				
				// player might have injury
				$pInjured[TRUE] = min(99, round(((100 - $player->strengthFreshness) / 3) * $this->_websoccer->getConfig('sim_injuredprobability') / 100));
				$pInjured[FALSE] = 100 - $pInjured[TRUE];
				$injured = SimulationHelper::selectItemFromProbabilities($pInjured);
				$blockedMatches = 0;
				if ($injured) {
					$maxMatchesInjured = (int) $this->_websoccer->getConfig('sim_maxmatches_injured');
					$pInjuredMatches[1] = 5;
					$pInjuredMatches[2] = 25;
					$pInjuredMatches[3] = 30;
					$pInjuredMatches[4] = 20;
					$pInjuredMatches[5] = 5;
					$pInjuredMatches[6] = 5;
					$pInjuredMatches[7] = 5;
					$pInjuredMatches[8] = 1;
					$pInjuredMatches[9] = 1;
					$pInjuredMatches[10] = 1;
					$pInjuredMatches[11] = 1;
					$pInjuredMatches[$maxMatchesInjured] = 1;
					$blockedMatches = SimulationHelper::selectItemFromProbabilities($pInjuredMatches);
					$blockedMatches = min($maxMatchesInjured, $blockedMatches);
				}			
				
				foreach ($this->_observers as $observer) {
					if ($tackled == 'yellow') {
						$observer->onYellowCard($match, $opponent);
					} else {
						
						// number of blocked matches
						$maxMatchesBlocked = (int) $this->_websoccer->getConfig('sim_maxmatches_blocked');
						$minMatchesBlocked = min(1, $maxMatchesBlocked);
						$blockedMatchesRedCard = SimulationHelper::getMagicNumber($minMatchesBlocked, $maxMatchesBlocked);
						$observer->onRedCard($match, $opponent, $blockedMatchesRedCard);
					}
					
					if ($injured) {
						$observer->onInjury($match, $player, $blockedMatches);
						
						// select another player
						$match->setPlayerWithBall(SimulationHelper::selectPlayer($player->team, PLAYER_POSITION_MIDFIELD));
					}
				}
				
				// if player is a striker, he might be fouled within the goal room -> penalty
				if ($player->position == PLAYER_POSITION_STRIKER) {
					$pPenalty[TRUE] = 10;
					$pPenalty[FALSE] = 90;
					if (SimulationHelper::selectItemFromProbabilities($pPenalty)) {
						$this->foulPenalty($match, $player->team);
					}
					
					// fouls on all other player lead to a free kick
				} else {
					
					// select player who will shoot
					if ($player->team->freeKickPlayer != NULL) {
						$freeKickScorer = $player->team->freeKickPlayer;
					} else {
						$freeKickScorer = SimulationHelper::selectPlayer($player->team, PLAYER_POSITION_MIDFIELD);
					}
					
					// get goaly influence
					$goaly = SimulationHelper::selectPlayer(SimulationHelper::getOpponentTeam($freeKickScorer, $match), PLAYER_POSITION_GOALY, null);
					$goalyInfluence = (int) $this->_websoccer->getConfig('sim_goaly_influence');
					$shootReduction = round($goaly->getTotalStrength($this->_websoccer, $match) * $goalyInfluence/100);
					
					// do not consider position dependent shoot strength here
					$shootStrength = $freeKickScorer->getTotalStrength($this->_websoccer, $match);
					
					$pGoal[TRUE] = max(1, min($shootStrength - $shootReduction, 60));
					$pGoal[FALSE] = 100 - $pGoal[TRUE];
					
					$freeKickResult = SimulationHelper::selectItemFromProbabilities($pGoal);
					foreach ($this->_observers as $observer) {
						$observer->onFreeKick($match, $freeKickScorer, $goaly, $freeKickResult);
					}
					
					if ($freeKickResult) {
						$this->_kickoff($match, $freeKickScorer);
					} else {
						$match->setPlayerWithBall($goaly);
					}
					
				}

			}
			
			// player lost the ball
		} else {
			
			$match->setPlayerWithBall($opponent);
			
			// try a counterattack if player who lost the ball was an attacking player and if team is supposed to focus on counterattacks.
			if ($player->position == PLAYER_POSITION_STRIKER && $opponent->team->counterattacks) {
				
				// actual attempt depends also on tactic of other team
				$counterAttempt[TRUE] = $player->team->offensive;
				$counterAttempt[FALSE] = 100 - $counterAttempt[TRUE];
				if (SimulationHelper::selectItemFromProbabilities($counterAttempt)) {
					// first pass to a striker if player is defender
					if ($opponent->position == PLAYER_POSITION_DEFENCE) {
						$match->setPlayerWithBall(SimulationHelper::selectPlayer($opponent->team, PLAYER_POSITION_STRIKER));
					}
					$this->shoot($match);
				}
			}
			
		}
		
		return $result;
	}

	/**
	 * @see ISimulationStrategy::shoot()
	 */
	public function shoot(SimulationMatch $match) {
		$player = $match->getPlayerWithBall();
		$goaly = SimulationHelper::selectPlayer(SimulationHelper::getOpponentTeam($player, $match), PLAYER_POSITION_GOALY, null);
		
		// get goaly influence from settings. 20 = 20%
		$goalyInfluence = (int) $this->_websoccer->getConfig('sim_goaly_influence');
		$shootReduction = round($goaly->getTotalStrength($this->_websoccer, $match) * $goalyInfluence/100);
		
		// increase / reduce shooting strength by position
		$shootStrength = round($player->getTotalStrength($this->_websoccer, $match) * $this->_shootStrengthPerPosition[$player->position] / 100);
		
		// increase chance with every failed attempt, except when player is only striker - then many attempts are natural.
		if ($player->position != PLAYER_POSITION_STRIKER || 
				isset($player->team->positionsAndPlayers[PLAYER_POSITION_STRIKER]) 
				&& count($player->team->positionsAndPlayers[PLAYER_POSITION_STRIKER]) > 1) {
			$shootStrength = $shootStrength + $player->getShoots() * 2 - $player->getGoals();
		}
		
		// reduce probability of too many goals per scorer
		if ($player->getGoals() > 1) {
			$shootStrength = round($shootStrength / $player->getGoals());
		}
		
		$pGoal[TRUE] = max(1, min($shootStrength - $shootReduction, 60));
		$pGoal[FALSE] = 100 - $pGoal[TRUE];
		
		$result = SimulationHelper::selectItemFromProbabilities($pGoal);
		
		// missed
		if ($result == FALSE) {
			foreach ($this->_observers as $observer) {
				$observer->onShootFailure($match, $player, $goaly);
			}
			
			// always give ball to goaly
			$match->setPlayerWithBall($goaly);
			
			// resulted in a corner? Depends on player's strength
			$pCorner[TRUE] = round($player->strength / 2);
			$pCorner[FALSE] = 100 - $pCorner[TRUE];
			if (SimulationHelper::selectItemFromProbabilities($pCorner)) {
				
				// select players
				if ($player->team->freeKickPlayer) {
					$passingPlayer = $player->team->freeKickPlayer;
				} else {
					$passingPlayer = SimulationHelper::selectPlayer($player->team, PLAYER_POSITION_MIDFIELD);
				}
				
				$targetPlayer = SimulationHelper::selectPlayer($player->team, PLAYER_POSITION_MIDFIELD, $passingPlayer);
				foreach ($this->_observers as $observer) {
					$observer->onCorner($match, $passingPlayer, $targetPlayer);
				}
				$match->setPlayerWithBall($targetPlayer);
			}
			
		// scored
		} else {
			foreach ($this->_observers as $observer) {
				$observer->onGoal($match, $player, $goaly);
			}
			$this->_kickoff($match, $player);
		}
		
		return $result;
	}
	
	/**
	 * @see ISimulationStrategy::penaltyShooting()
	 */
	public function penaltyShooting(SimulationMatch $match) {
		$shots = 0;
		$goalsHome = 0;
		$goalsGuest = 0;
		
		$playersHome = SimulationHelper::getPlayersForPenaltyShooting($match->homeTeam);
		$playersGuest = SimulationHelper::getPlayersForPenaltyShooting($match->guestTeam);
		
		// rules: first 5 shots; check if there is a winner
		// if not winner: each attempt can lead to winner.
		// exception from official rules: home always starts (emotions are anyway not simulated); order of players is always same (according to players' strength)
		// break after 50 shots, because most probably something went wrong then.
		
		$playerIndexHome = 0;
		$playerIndexGuest = 0;
		while ($shots <= 50) {
			$shots++;
			
			// home team shoots
			if ($this->_shootPenalty($match, $playersHome[$playerIndexHome])) {
				$goalsHome++;
			}
			
			// guest team shoots
			if ($this->_shootPenalty($match, $playersGuest[$playerIndexGuest])) {
				$goalsGuest++;
			}
			
			// do we have a winner?
			if ($shots >= 5 && $goalsHome !== $goalsGuest) {
				return TRUE;
			}
			
			$playerIndexHome++;
			$playerIndexGuest++;
			
			if ($playerIndexHome >= count($playersHome)) {
				$playerIndexHome = 0;
			}
			if ($playerIndexGuest >= count($playersGuest)) {
				$playerIndexGuest = 0;
			}
		}
	}
	
	private function foulPenalty(SimulationMatch $match, SimulationTeam $team) {
		// select player to shoot (strongest)
		$players = SimulationHelper::getPlayersForPenaltyShooting($team);
		$player = $players[0];
		
		$match->setPlayerWithBall($player);
		
		// execute shoot
		if ($this->_shootPenalty($match, $player)) {
			
			// if hit, only update player's statistic, since other statistics (such as team goals, grades) will be auomatically updated by observers
			$player->setGoals($player->getGoals() + 1);
		} else {
			// choose goaly as next player
			$goaly = SimulationHelper::selectPlayer(SimulationHelper::getOpponentTeam($player, $match), PLAYER_POSITION_GOALY, null);
			$match->setPlayerWithBall($goaly);
		}
	}
	
	private function _setPassTargetProbabilities() {
		$this->_passTargetProbPerPosition[PLAYER_POSITION_GOALY][PLAYER_POSITION_GOALY] = 0;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_GOALY][PLAYER_POSITION_DEFENCE] = 69;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_GOALY][PLAYER_POSITION_MIDFIELD] = 30;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_GOALY][PLAYER_POSITION_STRIKER] = 1;
	
		$this->_passTargetProbPerPosition[PLAYER_POSITION_DEFENCE][PLAYER_POSITION_GOALY] = 10;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_DEFENCE][PLAYER_POSITION_DEFENCE] = 20;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_DEFENCE][PLAYER_POSITION_MIDFIELD] = 65;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_DEFENCE][PLAYER_POSITION_STRIKER] = 5;
	
		$this->_passTargetProbPerPosition[PLAYER_POSITION_MIDFIELD][PLAYER_POSITION_GOALY] = 1;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_MIDFIELD][PLAYER_POSITION_DEFENCE] = 24;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_MIDFIELD][PLAYER_POSITION_MIDFIELD] = 55;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_MIDFIELD][PLAYER_POSITION_STRIKER] = 20;
	
		$this->_passTargetProbPerPosition[PLAYER_POSITION_STRIKER][PLAYER_POSITION_GOALY] = 0;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_STRIKER][PLAYER_POSITION_DEFENCE] = 10;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_STRIKER][PLAYER_POSITION_MIDFIELD] = 60;
		$this->_passTargetProbPerPosition[PLAYER_POSITION_STRIKER][PLAYER_POSITION_STRIKER] = 30;
	}
	
	private function _setOpponentPositions() {
		$this->_opponentPositions[PLAYER_POSITION_GOALY] = PLAYER_POSITION_STRIKER;
		$this->_opponentPositions[PLAYER_POSITION_DEFENCE] = PLAYER_POSITION_STRIKER;
		$this->_opponentPositions[PLAYER_POSITION_MIDFIELD] = PLAYER_POSITION_MIDFIELD;
		$this->_opponentPositions[PLAYER_POSITION_STRIKER] = PLAYER_POSITION_DEFENCE;
	}
	
	private function _setShootProbPerPosition() {
		$this->_shootProbPerPosition[PLAYER_POSITION_GOALY] = 0;
		$this->_shootProbPerPosition[PLAYER_POSITION_DEFENCE] = 5;
		$this->_shootProbPerPosition[PLAYER_POSITION_MIDFIELD] = 20;
		$this->_shootProbPerPosition[PLAYER_POSITION_STRIKER] = 35;
	}
	
	private function _setShootStrengthPerPosition() {
		$this->_shootStrengthPerPosition[PLAYER_POSITION_GOALY] = 10;
		$this->_shootStrengthPerPosition[PLAYER_POSITION_DEFENCE] = $this->_websoccer->getConfig('sim_shootstrength_defense');
		$this->_shootStrengthPerPosition[PLAYER_POSITION_MIDFIELD] = $this->_websoccer->getConfig('sim_shootstrength_midfield');
		$this->_shootStrengthPerPosition[PLAYER_POSITION_STRIKER] = $this->_websoccer->getConfig('sim_shootstrength_striker');
	}
	
	private function _getOffensiveStrength($team, $match) {
		
		$strength = 0;
		
		// midfield
		if (isset($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD])) {
			
			$omPlayers = 0;
			foreach ($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD] as $player) {
				$mfStrength = $player->getTotalStrength($this->_websoccer, $match);
				
				// add 30% for attacking midfielders, reduce 30% if defensive
				if ($player->mainPosition == 'OM') {
					$omPlayers++;
					// only up to 3 OMs are effective. Else, players in defense are missing for building attacks
					if ($omPlayers <= 3) {
						$mfStrength = $mfStrength * 1.3;
					} else {
						$mfStrength = $mfStrength * 0.5;
					}
					
				} else if ($player->mainPosition == 'DM') {
					$mfStrength = $mfStrength * 0.7;
				}
				
				$strength += $mfStrength;
			}
		}
		
		// strikers (count only first two doubled since too many strikers are inefficient)
		$noOfStrikers = 0;
		if (isset($team->positionsAndPlayers[PLAYER_POSITION_STRIKER])) {
			foreach ($team->positionsAndPlayers[PLAYER_POSITION_STRIKER] as $player) {
				$noOfStrikers++;
				
				if ($noOfStrikers < 3) {
					$strength += $player->getTotalStrength($this->_websoccer, $match) * 1.5;
				} else {
					$strength += $player->getTotalStrength($this->_websoccer, $match) * 0.5;
				}
			}
		}
		
		$offensiveFactor = (80 + $team->offensive * 0.4) / 100;
		$strength = $strength * $offensiveFactor;
		
		return $strength;
	}
	
	private function _getDefensiveStrength(SimulationTeam $team, $match) {
	
		$strength = 0;
	
		// midfield
		foreach ($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD] as $player) {
			$mfStrength = $player->getTotalStrength($this->_websoccer, $match);
			
			// add 30% for defensive midfielders, reduce 30% if attacking
			if ($player->mainPosition == 'OM') {
				$mfStrength = $mfStrength * 0.7;
			} else if ($player->mainPosition == 'DM') {
				$mfStrength = $mfStrength * 1.3;
			}
			
			// give bonus on midfielders when team is supposed to be focussed on counterattacks. They will be more defending then.
			if ($team->counterattacks) {
				$mfStrength = $mfStrength * 1.1;
			}
			
			$strength += $mfStrength;
		}
	
		// defense
		$noOfDefence = 0;
		foreach ($team->positionsAndPlayers[PLAYER_POSITION_DEFENCE] as $player) {
			$noOfDefence++;
			$strength += $player->getTotalStrength($this->_websoccer, $match);
		}
		
		// less than 3 defence players would be extra risky
		if ($noOfDefence < 3) {
			$strength = $strength * 0.5;
			
			// but more than 4 extra secure
		} else if ($noOfDefence > 4) {
			$strength = $strength * 1.5;
		}
	
		// tactic
		$offensiveFactor = (130 - $team->offensive * 0.5) / 100;
		$strength = $strength * $offensiveFactor;
		
		return $strength;
	}
	
	private function _shootPenalty(SimulationMatch $match, SimulationPlayer $player) {
		
		$goaly = SimulationHelper::selectPlayer(SimulationHelper::getOpponentTeam($player, $match), PLAYER_POSITION_GOALY, null);
		
		// get goaly influence from settings. 20 = 20%
		$goalyInfluence = (int) $this->_websoccer->getConfig('sim_goaly_influence');
		$shootReduction = round($goaly->getTotalStrength($this->_websoccer, $match) * $goalyInfluence/100);
		
		// probability is between 30 and 80%
		$pGoal[TRUE] = max(30, min($player->strength - $shootReduction, 80));
		$pGoal[FALSE] = 100 - $pGoal[TRUE];
		
		$result = SimulationHelper::selectItemFromProbabilities($pGoal);
		
		foreach ($this->_observers as $observer) {
			$observer->onPenaltyShoot($match, $player, $goaly, $result);
		}
		
		return $result;
	}
	
	private function _kickoff(SimulationMatch $match, SimulationPlayer $scorer) {
		// let kick-off the opponent
		$match->setPlayerWithBall(
				SimulationHelper::selectPlayer(SimulationHelper::getOpponentTeam($scorer, $match), PLAYER_POSITION_DEFENCE, null));
	}
	
}
?>