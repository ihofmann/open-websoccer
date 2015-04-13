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
 * Holds the state of a match during a match simulation.
 * 
 * @author Ingo Hofmann
 */
class SimulationMatch {

	/**
	 * @var int match ID
	 */
	public $id;
	
	/**
	 * @var string match type, as defined in the database.
	 */
	public $type;
	
	/**
	 * @var SimulationTeam home team.
	 */
	public $homeTeam;
	
	/**
	 * @var SimulationTeam guest team.
	 */
	public $guestTeam;
	
	/**
	 * @var int current simulation minute (= how many minutes have been simulated so far + 1).
	 */
	public $minute;
	
	/**
	 * @var boolean TRUE if penaly shooting shall be simulated in case there is no winner.
	 */
	public $penaltyShootingEnabled;
	
	/**
	 * @var boolean TRUE if match has been fully simulated.
	 */
	public $isCompleted;
	
	/**
	 * @var string cup name in case it is a cup match.
	 */
	public $cupName;
	
	/**
	 * @var string cup round name in case it is a cup match.
	 */
	public $cupRoundName;
	
	/**
	 * @var string cup round group name in case it is a cup match.
	 */
	public $cupRoundGroup;
	
	/**
	 * @var boolean TRUE if all tickets are sold. FALSE otherwise.
	 */
	public $isSoldOut;
	
	/**
	 * @var SimulationPlayer player who currently has the ball.
	 */
	private $playerWithBall;
	
	/**
	 * @var SimulationPlayer player who previously had the ball. Will be stored in order to identfy goal assists.
	 */
	private $previousPlayerWithBall;
	
	/**
	 * @var boolean TRUE if stadium is not home team's one.
	 */
	public $isAtForeignStadium;
    
	/**
	 * Creates new match model with defaul values.
	 * 
	 * @param int $id match ID
	 * @param SimulationTeam $homeTeam home team.
	 * @param SimulationTeam $guestTeam guest team.
	 * @param int $minute current simulation minute (= how many minutes have been simulated so far + 1).
	 * @param SimulationPlayer $playerWithBall player who currently has the ball.
	 * @param SimulationPlayer $previousPlayerWithBall player who previously had the ball. Will be stored in order to identfy goal assists.
	 */
    public function __construct($id, $homeTeam, $guestTeam, $minute, $playerWithBall = null, $previousPlayerWithBall = null) {
    	$this->id = $id;
    	$this->homeTeam = $homeTeam;
    	$this->guestTeam = $guestTeam;
    	$this->minute = $minute;
    	$this->playerWithBall = $playerWithBall;
    	$this->previousPlayerWithBall = $previousPlayerWithBall;
    	
    	$this->isCompleted = FALSE;
    	$this->penaltyShootingEnabled = FALSE;
    	$this->isSoldOut = FALSE;
    }
    
    /**
     * @return SimulationPlayer player who currently has the ball.
     */
    public function getPlayerWithBall() {
    	return $this->playerWithBall;
    }
    
    /**
     * @return SimulationPlayer player who previously had the ball. Will be stored in order to identfy goal assists.
     */
    public function getPreviousPlayerWithBall() {
    	return $this->previousPlayerWithBall;
    }
    
    /**
     * @param SimulationPlayer $player player to set
     */
    public function setPreviousPlayerWithBall($player) {
    	$this->previousPlayerWithBall = $player;
    }
    
    /**
     * Sets the flag for current player with ball. Also increases statistics for ball contacts and moves existing player with ball
     * to previous player with ball.
     * 
     * @param SimulationPlayer $player player to set
     */
    public function setPlayerWithBall($player) {
    	if ($this->playerWithBall !== NULL && $this->playerWithBall->id !== $player->id) {
    		$player->setBallContacts($player->getBallContacts() + 1);
    		
    		$this->previousPlayerWithBall = $this->playerWithBall;
    	}
    	
    	$this->playerWithBall = $player;
    }
    
    /**
     * Unsets all object refrences in order to get destroyed by the garbage collector.
     */
    public function cleanReferences() {
    	$this->homeTeam->cleanReferences();
    	$this->guestTeam->cleanReferences();
    	
    	unset($this->homeTeam);
    	unset($this->guestTeam);
    	unset($this->playerWithBall);
    	unset($this->previousPlayerWithBall);
    }
    
}
?>