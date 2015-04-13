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

// define mark/grade improvements or downgrades for particular events.
define('MARK_IMPROVE_GOAL_SCORER', 1);
define('MARK_IMPROVE_GOAL_PASSPLAYER', 0.75);
define('MARK_DOWNGRADE_GOAL_GOALY', 0.5);
define('MARK_DOWNGRADE_SHOOTFAILURE', 0.5);
define('MARK_IMPROVE_SHOOTFAILURE_GOALY', 0.5);
define('MARK_IMPROVE_TACKLE_WINNER', 0.25);
define('MARK_DOWNGRADE_TACKLE_LOOSER', 0.5);
define('MARK_DOWNGRADE_BALLPASS_FAILURE', 0.25);
define('MARK_IMPROVE_BALLPASS_SUCCESS', 0.1);

/**
 * The default simulation observer updates statistics and player grades and controls playerWithBall on appropriate events.
 * 
 * @author Ingo Hofmann
 */
class DefaultSimulationObserver implements ISimulationObserver {

	/**
	 * @see ISimulationObserver::onGoal()
	 */
	public function onGoal(SimulationMatch $match, SimulationPlayer $scorer, SimulationPlayer $goaly) {
		$assistPlayer = ($match->getPreviousPlayerWithBall() !== NULL && $match->getPreviousPlayerWithBall()->team->id == $scorer->team->id) ? $match->getPreviousPlayerWithBall() : '';
		
		$scorer->improveMark(MARK_IMPROVE_GOAL_SCORER);
		$goaly->downgradeMark(MARK_DOWNGRADE_GOAL_GOALY);
		
		// pass player
		if (strlen($assistPlayer)) {
			$assistPlayer->improveMark(MARK_IMPROVE_GOAL_PASSPLAYER);
			$assistPlayer->setAssists($assistPlayer->getAssists() + 1);
		}
		
		// update result
		$scorer->team->setGoals($scorer->team->getGoals() + 1);
		$scorer->setGoals($scorer->getGoals() + 1);
		
		$scorer->setShoots($scorer->getShoots() + 1);
	}
	
	/**
	 * @see ISimulationObserver::onShootFailure()
	 */
	public function onShootFailure(SimulationMatch $match, SimulationPlayer $scorer, SimulationPlayer $goaly) {
		
		// downgrade striker, if he isno hero yet (= scored at least two goals)
		if ($scorer->getGoals() < 3) {
			$scorer->downgradeMark(MARK_DOWNGRADE_SHOOTFAILURE);
		}
		
		$goaly->improveMark(MARK_IMPROVE_SHOOTFAILURE_GOALY);
		// consider goals against goaly, in order to prevent goalies in team of the day, even though team lost very high.
		if ($goaly->team->getGoals() > 3) {
			$goaly->setMark(max(2.0, $goaly->getMark()));
		}
		
		$scorer->setShoots($scorer->getShoots() + 1);
	}
	
	/**
	 * @see ISimulationObserver::onAfterTackle()
	 */
	public function onAfterTackle(SimulationMatch $match, SimulationPlayer $winner, SimulationPlayer $looser) {
		
		// show mercy when player already is a hero
		if ($looser->getGoals() > 0 && $looser->getGoals() < 3 && $looser->getAssists() > 0 && $looser->getAssists() < 3) {
			$looser->downgradeMark(MARK_DOWNGRADE_TACKLE_LOOSER * 0.5);
		} elseif ($looser->getGoals() < 3 && $looser->getAssists() < 3) {
			$looser->downgradeMark(MARK_DOWNGRADE_TACKLE_LOOSER);
		}
		
		$winner->improveMark(MARK_IMPROVE_TACKLE_WINNER);
		
		$winner->setWonTackles($winner->getWonTackles() + 1);
		$looser->setLostTackles($winner->getLostTackles() + 1);
	}
	
	/**
	 * @see ISimulationObserver::onBallPassSuccess()
	 */
	public function onBallPassSuccess(SimulationMatch $match, SimulationPlayer $player) {
		$player->improveMark(MARK_IMPROVE_BALLPASS_SUCCESS);
		
		$player->setPassesSuccessed($player->getPassesSuccessed() + 1);
	}
	
	/**
	 * @see ISimulationObserver::onBallPassFailure()
	 */
	public function onBallPassFailure(SimulationMatch $match, SimulationPlayer $player) {
		
		// show mercy when player already hit at least twice or assisted twice or both scored and assisted
		if ($player->getGoals() < 2 && $player->getAssists() < 2
				&& ($player->getGoals() == 0 || $player->getAssists() == 0)) {
			$player->downgradeMark(MARK_DOWNGRADE_BALLPASS_FAILURE);
		}
		
		$player->setPassesFailed($player->getPassesFailed() + 1);
	}
	
	/**
	 * @see ISimulationObserver::onInjury()
	 */
	public function onInjury(SimulationMatch $match, SimulationPlayer $player, $numberOfMatches) {
		$player->injured = $numberOfMatches;
		
		// try to substitute in next minute
		$substituted = SimulationHelper::createUnplannedSubstitutionForPlayer($match->minute + 1, $player);
			
		// not possible, hence just remove player
		if (!$substituted) {
			$player->team->removePlayer($player);
		}
		
	}
	
	/**
	 * @see ISimulationObserver::onYellowCard()
	 */
	public function onYellowCard(SimulationMatch $match, SimulationPlayer $player) {
		$player->yellowCards = $player->yellowCards + 1;
		
		if ($player->yellowCards == 2) {
			$player->downgradeMark(MARK_DOWNGRADE_TACKLE_LOOSER);
			$player->team->removePlayer($player);
		}
		
	}
	
	/**
	 * @see ISimulationObserver::onRedCard()
	 */
	public function onRedCard(SimulationMatch $match, SimulationPlayer $player, $matchesBlocked) {
		$player->redCard = 1;
		
		$player->blocked = $matchesBlocked;
		
		$player->team->removePlayer($player);
	}
	
	/**
	 * @see ISimulationObserver::onPenaltyShoot()
	 */
	public function onPenaltyShoot(SimulationMatch $match, SimulationPlayer $player, SimulationPlayer $goaly, $successful) {
		// adapt grades
		if ($successful) {
			$player->improveMark(MARK_IMPROVE_GOAL_SCORER);
			// do not decrease goaly's grade since it is probably not his fault, poor guy..
		
			// update goals
			$player->team->setGoals($player->team->getGoals() + 1);
		} else {
			$player->downgradeMark(MARK_DOWNGRADE_SHOOTFAILURE);
			$goaly->improveMark(MARK_IMPROVE_SHOOTFAILURE_GOALY);
		}
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISimulationObserver::onCorner()
	 */
	public function onCorner(SimulationMatch $match, SimulationPlayer $concededByPlayer, SimulationPlayer $targetPlayer) {
		
		// simply improve mark for successful pass
		$match->setPlayerWithBall($concededByPlayer);
		$concededByPlayer->improveMark(MARK_IMPROVE_BALLPASS_SUCCESS);
		$concededByPlayer->setPassesSuccessed($concededByPlayer->getPassesSuccessed() + 1);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISimulationObserver::onFreeKick()
	 */
	public function onFreeKick(SimulationMatch $match, SimulationPlayer $player, SimulationPlayer $goaly, $successful) {
		$player->setShoots($player->getShoots() + 1);
		
		// adapt grades
		if ($successful) {
			$player->improveMark(MARK_IMPROVE_GOAL_SCORER);
			// do not decrease goaly's grade since it is probably not his fault, poor guy..
	
			// update goals
			$player->team->setGoals($player->team->getGoals() + 1);
			$player->setGoals($player->getGoals() + 1);
		} else {
			$player->downgradeMark(MARK_DOWNGRADE_SHOOTFAILURE);
			$goaly->improveMark(MARK_IMPROVE_SHOOTFAILURE_GOALY);
		}
	
	}
	
}
?>