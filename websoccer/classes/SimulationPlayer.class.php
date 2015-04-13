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
 * Represents a team's football player during a match simulation.
 * 
 * @author Ingo Hofmann
 */
class SimulationPlayer {

	/**
	 * @var int Player ID as in database
	 */
	public $id;
	
	/**
	 * @var SimulationTeam player's team
	 */
	public $team;
	
	/**
	 * @var string first and last name
	 */
	public $name;
	
	/**
	 * @var general position (as in DB) on which player is actually playing during this match. 
	 */
	public $position;
	
	/**
	 * @var main position (as in DB) on which player is actually playing during this match. 
	 * NOTE that before version 4.0.0, this had been the player's main position, regardless of actual positiom.
	 */
	public $mainPosition;
	
	/**
	 * @var int age in years.
	 */
	public $age;
	
	/**
	 * @var int strength attribute: strength (in per cent). Including strength weakness due to wrong position.
	 */
	public $strength;
	
	/**
	 * @var int strength attribute: technique (in per cent)
	 */
	public $strengthTech;
	
	/**
	 * @var int strength attribute: stamina (in per cent)
	 */
	public $strengthStamina;
	
	/**
	 * @var int strength attribute: freshness (in per cent)
	 */
	public $strengthFreshness;
	
	/**
	 * @var int strength attribute: satisfaction (in per cent)
	 */
	public $strengthSatisfaction;
	
	/**
	 * @var int number of yellow cards got
	 */
	public $yellowCards;
	
	/**
	 * @var int number of red cards got
	 */
	public $redCard;
	
	/**
	 * @var int number of next matches which he cannot play due to an injury.
	 */
	public $injured;
	
	/**
	 * @var int number of next matches which he cannot play due to a (yellow-)red card.
	 */
	public $blocked;
	
	/**
	 * @var int number of goals that this player scored.
	 */
	public $goals;
	
	private $minutesPlayed;
	private $totalStrength;
	private $mark;
	
	private $ballContacts;
	private $wonTackles;
	private $lostTackles;
	private $shoots;
	private $passesSuccessed;
	private $passesFailed;
	private $assists;
	
	private $needsStrengthRecomputation;
	
    public function __construct($id, $team, $position, $mainPosition, 
    		$mark, $age, $strength, $strengthTech, $strengthStamina, $strengthFreshness, $strengthSatisfaction) {
    	$this->id = $id;
    	$this->team = $team;
    	$this->position = $position;
    	$this->mainPosition = $mainPosition;
    	$this->mark = $mark;
    	$this->age = $age;
    	$this->strength = $strength;
    	$this->strengthTech = $strengthTech;
    	$this->strengthStamina = $strengthStamina;
    	$this->strengthFreshness = $strengthFreshness;
    	$this->strengthSatisfaction = $strengthSatisfaction;
    	
    	$this->injured = 0;
    	$this->blocked = 0;
    	$this->goals = 0;
    	
    	$this->minutesPlayed = 0;
    	
    	$this->ballContacts = 0;
    	$this->wonTackles = 0;
    	$this->lostTackles = 0;
    	$this->shoots = 0;
    	$this->passesSuccessed = 0;
    	$this->passesFailed = 0;
    	$this->assists = 0;
    }
    
    /**
     * Player's current total strength. Considering configured weights and player's mark / grade.
     * 
     * @param WebSoccer $websoccer application context.
     * @param SimulationMatch $match match model.
     */
    public function getTotalStrength(WebSoccer $websoccer, SimulationMatch $match) {
    	if ($this->totalStrength == null || $this->needsStrengthRecomputation == TRUE) {
    		$this->recomputeTotalStrength($websoccer,$match);
    	}
    	return $this->totalStrength;
    } 
    
    /**
     * @return double current grade / mark.
     */
    public function getMark() {
    	return $this->mark;
    }
    
    /**
     * @param double $mark grade / mark to set.
     */
    public function setMark($mark) {
    	if ($this->mark !== $mark) {
    		$this->mark = $mark;
    		
    		$this->needsStrengthRecomputation = TRUE;
    	}
    }
    
    /**
     * Improves the player's current grade.
     * 
     * @param double $improvement grade improvement
     */
    public function improveMark($improvement) {
    	$newMark = max((float) $this->mark - $improvement, 1);
    	$this->setMark($newMark);
    }
    
    /**
     * downgrades player.
     * 
     * @param double $downgrade downgrade
     */
    public function downgradeMark($downgrade) {
    	$newMark = min((float) $this->mark + $downgrade, 6);
    	$this->setMark($newMark);
    }
    
    /**
     * Triggers a recomputation of the total strength. This might be necessary when the player's grade or freshness has changed due
     * to the match simulation.
     * 
     * @param WebSoccer $websoccer application context.
     * @param SimulationMatch $match match model.
     */
    public function recomputeTotalStrength(WebSoccer $websoccer, SimulationMatch $match) {
    	$mainStrength = $this->strength;
    	
    	// home field advantage
    	if ($match->isSoldOut && $this->team->id == $match->homeTeam->id) {
	    	$mainStrength += $websoccer->getConfig("sim_home_field_advantage");
    	}
    	
    	// weakening of NAs
    	if ($this->team->noFormationSet) {
    		$mainStrength = round($mainStrength * $websoccer->getConfig("sim_createformation_strength") / 100);
    	}
    	
    	$weightsSum = $websoccer->getConfig("sim_weight_strength")
    		+ $websoccer->getConfig("sim_weight_strengthTech")
    		+ $websoccer->getConfig("sim_weight_strengthStamina")
    		+ $websoccer->getConfig("sim_weight_strengthFreshness")
    		+ $websoccer->getConfig("sim_weight_strengthSatisfaction");
    		
    	// get weights from settings
    	$totalStrength = $mainStrength * $websoccer->getConfig("sim_weight_strength"); 
    	$totalStrength += $this->strengthTech * $websoccer->getConfig("sim_weight_strengthTech"); 
    	$totalStrength += $this->strengthStamina * $websoccer->getConfig("sim_weight_strengthStamina"); 
    	$totalStrength += $this->strengthFreshness * $websoccer->getConfig("sim_weight_strengthFreshness");
    	$totalStrength += $this->strengthSatisfaction * $websoccer->getConfig("sim_weight_strengthSatisfaction");
    	$totalStrength = $totalStrength / $weightsSum;
    	
    	// consider mark (1.0 -> +10%, 6.0 -> -10%)
    	$totalStrength = $totalStrength * (114 - 4 * $this->mark) / 100;
    	
    	$this->totalStrength = min(100, round($totalStrength));
    	
    	$this->needsStrengthRecomputation = FALSE;
    }
    
    /**
     * @return int Number of tackles that the player won.
     */
    public function getWonTackles() {
    	return $this->wonTackles;
    }
    
    /**
     * 
     * @param int $wonTackles Number of tackles that the player won.
     */
    public function setWonTackles($wonTackles) {
    	if ($this->wonTackles !== $wonTackles) {
    		$this->wonTackles = $wonTackles;
    		
    	}
    }
    
    /**
     * @return int Number of tackles that the player lost.
     */
    public function getLostTackles() {
    	return $this->lostTackles;
    }
    
    /**
     *
     * @param int $wonTackles Number of tackles that the player lost.
     */
    public function setLostTackles($lostTackles) {
    	if ($this->lostTackles !== $lostTackles) {
    		$this->lostTackles = $lostTackles;
    	}
    }
    
    /**
     * 
     * @return int number of successful passes by the player.
     */
    public function getPassesSuccessed() {
    	return $this->passesSuccessed;
    }
    
    /**
     * @param int $passesSuccessed number of successful passes by the player.
     */
    public function setPassesSuccessed($passesSuccessed) {
    	if ($this->passesSuccessed !== $passesSuccessed) {
    		$this->passesSuccessed = $passesSuccessed;
    		
    	}
    }
    
    /**
     * @return int number of unseccessful passes by the player
     */
    public function getPassesFailed() {
    	return $this->passesFailed;
    }
    
    /**
     * @param int $passesFailed number of unseccessful passes by the player
     */
    public function setPassesFailed($passesFailed) {
    	if ($this->passesFailed !== $passesFailed) {
    		$this->passesFailed = $passesFailed;
    		
    	}
    }
    
    /**
     * @return int number of attempts by the player
     */
    public function getShoots() {
    	return $this->shoots;
    }
    
    /**
     * @param int $shoots number of attempts by the player
     */
    public function setShoots($shoots) {
    	if ($this->shoots !== $shoots) {
    		$this->shoots = $shoots;
    		
    	}
    }
    
    /**
     * @return int number of ball contacts during the match.
     */
    public function getBallContacts() {
    	return $this->ballContacts;
    }
    
    /**
     * @param int $ballContacts number of ball contacts during the match.
     */
    public function setBallContacts($ballContacts) {
    	if ($this->ballContacts !== $ballContacts) {
    		$this->ballContacts = $ballContacts;
    		
    	}
    }
    
    /**
     * @return int number of goals that the player scored.
     */
    public function getGoals() {
    	return $this->goals;
    }
    
    /**
     * @param int $goals number of goals that the player scored.
     */
    public function setGoals($goals) {
    	if ($this->goals !== $goals) {
    		$this->goals = $goals;
    		
    	}
    }
    
    /**
     * @return int number of goal assists.
     */
    public function getAssists() {
    	return $this->assists;
    }
    
    /**
     * @param int $assists number of goal assists.
     */
    public function setAssists($assists) {
    	if ($this->assists !== $assists) {
    		$this->assists = $assists;
    
    	}
    }
    
    /**
     * @return int number of minutes that the player has been on the pitch.
     */
    public function getMinutesPlayed() {
    	return $this->minutesPlayed;
    }
    
    /**
     * 
     * @param int $minutesPlayed number of minutes that the player has been on the pitch.
     * @param boolean $recomputeFreshness TRUE (default) if the total strength shall be recomputed after setting the minutes. 
     * If so, player also looses freshness over time.
     */
    public function setMinutesPlayed($minutesPlayed, $recomputeFreshness = TRUE) {
    	if ($this->minutesPlayed < $minutesPlayed) {
    		$this->minutesPlayed = $minutesPlayed;
    
    		if ($recomputeFreshness && $minutesPlayed % 20 == 0) {
    			
    			// goaly looses only 1 freshness only after 20 minutes
    			if ($minutesPlayed == 20 && $this->position == PLAYER_POSITION_GOALY) {
    				$this->strengthFreshness = max(1, $this->strengthFreshness - 1);
    				$this->needsStrengthRecomputation = TRUE;
    			} else if ($this->position != PLAYER_POSITION_GOALY) {
    				$this->looseFreshness();
    			}
    			
    		}
    	}
    }
    
    /**
     * Unsets all object refrences in order to get destroyed by the garbage collector.
     */
    public function cleanReferences() {
    	unset($this->team);
    }
    
    private function looseFreshness() {
    	$freshness = $this->strengthFreshness - 1;
    	
    	if ($this->age > 32 && $this->position != PLAYER_POSITION_GOALY) {
    		$freshness -= 1;
    	}
    	
    	// playing offensive is extra tiring for offensive players
    	if ($this->team->offensive >= 80 && ($this->position == PLAYER_POSITION_MIDFIELD || $this->position == PLAYER_POSITION_STRIKER)) {
    		$freshness -= 1;
    	}
    	
    	// consider bad stamina
    	if ($this->strengthStamina < 40) {
    		$freshness -= 1;
    	}
    	
    	$freshness = max(1, $freshness);
    	$this->strengthFreshness = $freshness;
    	
    	$this->needsStrengthRecomputation = TRUE;
    }
    
    /**
     * 
     * @return string key atributes and their values.
     */
    public function __toString() {
    	return "{id: ". $this->id .", team: ". $this->team->id . ", position: ". $this->position .", mark: ". $this->mark 
    		.", strength: " . $this->strength . ", strengthTech: " . $this->strengthTech . ", strengthStamina: " . $this->strengthStamina . ", strengthFreshness: " . $this->strengthFreshness . ", strengthSatisfaction: " . $this->strengthSatisfaction . "}";
    }
	
}
?>