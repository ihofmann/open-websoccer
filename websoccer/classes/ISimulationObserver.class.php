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
 * Simulation observers allow actions on particular events during the match simulation.
 * They are called at the default simulation strategy implementation and registered at the simulator.
 * 
 * @see DefaultSimulationStrategy
 * @see Simulator
 * 
 * @author Ingo Hofmann
 */
interface ISimulationObserver {

	/**
	 * Specified scorer shot a goal.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $scorer player who shot the goal.
	 * @param SimulationPlayer $goaly Goalkeeper who could not prevent the goal.
	 */
	public function onGoal(SimulationMatch $match, SimulationPlayer $scorer, SimulationPlayer $goaly);
	
	/**
	 * Specified scorer tried to shoot a goal, but failed.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $scorer player who tried to hit.
	 * @param SimulationPlayer $goaly goalkeeper who could prevent the goal.
	 */
	public function onShootFailure(SimulationMatch $match, SimulationPlayer $scorer, SimulationPlayer $goaly);
	
	/**
	 * A tackle happened. Every tackle has a looser and winner. 
	 * Hint: Use SimulationMatch->getPlayerWithBall() in order to find out whether the winner is the player who has had already the ball and just could
	 * defend himself or if he gained the ball from the opponent player (=$looser).
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $winner tackler winner.
	 * @param SimulationPlayer $looser tackle looser.
	 */
	public function onAfterTackle(SimulationMatch $match, SimulationPlayer $winner, SimulationPlayer $looser);
	
	/**
	 * The ball has been passed successfully by the specified player. Use SimulationMatch->getPlayerWithBall() in order to find out who
	 * reached the ball.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player player who passed the ball.
	 */
	public function onBallPassSuccess(SimulationMatch $match, SimulationPlayer $player);
	
	/**
	 * The ball could NOT be passed successfully by the specified player. Use SimulationMatch->getPlayerWithBall() in order to find out who
	 * reached the ball.
	 *
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player player who tried to pass the ball, but failed.
	 */
	public function onBallPassFailure(SimulationMatch $match, SimulationPlayer $player);
	
	/**
	 * A player got injured.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player player who got injured.
	 * @param unknown $numberOfMatches Number of matches he has to pause.
	 */
	public function onInjury(SimulationMatch $match, SimulationPlayer $player, $numberOfMatches);
	
	/**
	 * The specified player got a yellow or yellow-red card. You know that it is a yellow-red card if SimulationPlayer->yellowCards == 2.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player player who got the yellow card.
	 */
	public function onYellowCard(SimulationMatch $match, SimulationPlayer $player);
	
	/**
	 * The specified player got a red card.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player Player who got the red card
	 * @param int $matchesBlocked Number of matches he will be blocked.
	 */
	public function onRedCard(SimulationMatch $match, SimulationPlayer $player, $matchesBlocked);
	
	/**
	 * A penalty has been conceded.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player player who conceded the penalty.
	 * @param SimulationPlayer $goaly Opponent team's goalkeeper.
	 * @param boolean $successful TRUE if penelty led to goal. FALSE if goalkeeper could prevent a goal.
	 */
	public function onPenaltyShoot(SimulationMatch $match, SimulationPlayer $player, SimulationPlayer $goaly, $successful);
	
	/**
	 * Corner, conceded by firstly specified player who passes the ball to the secondly specified player.
	 * 
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $concededByPlayer Player who conceds corner.
	 * @param SimulationPlayer $targetPlayer Player who gets the ball.
	 */
	public function onCorner(SimulationMatch $match, SimulationPlayer $concededByPlayer, SimulationPlayer $targetPlayer);
	
	/**
	 * A free kick has been conceded.
	 *
	 * @param SimulationMatch $match Affected match.
	 * @param SimulationPlayer $player player who conceded the free kick.
	 * @param SimulationPlayer $goaly Opponent team's goalkeeper.
	 * @param boolean $successful TRUE if free kick led to goal. FALSE if goalkeeper could prevent a goal.
	 */
	public function onFreeKick(SimulationMatch $match, SimulationPlayer $player, SimulationPlayer $goaly, $successful);
}
?>