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
define('SUB_CONDITION_TIE', 'Tie');
define('SUB_CONDITION_LEADING', 'Leading');
define('SUB_CONDITION_DEFICIT', 'Deficit');

/**
 * Represents a football player substitution during a match simulation.
 * 
 * @author Ingo Hofann
 */
class SimulationSubstitution {

	/**
	 * @var int minute at which the substitution is supposed to be executed.
	 */
	public $minute;
	
	/**
	 * @var SimulationPlayer player to come.
	 */
	public $playerIn;
	
	/**
	 * @var SimulationPlayer player to substitute. He must be present on the pitch, otherwise substitution will not be executed.
	 */
	public $playerOut;
	
	/**
	 * @var string constraint name which shall limit the execution on a condition. Is of value: null|Tie|Leading|Deficit
	 */
	public $condition;
	
	/**
	 * @var string target position identifier (T, LV, IV, ...).
	 */
	public $position;
	
	/**
	 * 
	 * @param int $minute minute at which the substitution is supposed to be executed.
	 * @param SimulationPlayer $playerIn player to come.
	 * @param SimulationPlayer $playerOut player to substitute. He must be present on the pitch, otherwise substitution will not be executed.
	 * @param string $condition constraint name which shall limit the execution on a condition. Value must be: null|Tie|Leading|Deficit
	 * @param string $position target position identifier (T, LV, IV, ...).
	 */
    public function __construct($minute, $playerIn, $playerOut, $condition = null, $position = null) {
    	$this->minute = $minute;
    	$this->playerIn = $playerIn;
    	$this->playerOut = $playerOut;
    	$this->position = $position;
    	
    	if ($condition != null 
    			&& in_array($condition, array(SUB_CONDITION_TIE, SUB_CONDITION_LEADING, SUB_CONDITION_DEFICIT))) {
    		$this->condition = $condition;
    	} else {
    		$this->condition = null;
    	}
    }
    
    /**
     * @return string property values.
     */
    public function __toString() {
    	return '{minute: '. $this->minute . ', in: '. $this->playerIn .', out: '. $this->playerOut . '}';
    }
	
}
?>