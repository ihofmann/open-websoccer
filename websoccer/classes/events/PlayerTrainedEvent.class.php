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
 * This event is triggered when a training unit execution is computed, just before
 * the training effect is saved in DB.
 */
class PlayerTrainedEvent extends AbstractEvent {
	
	/**
	 * @var int ID of player.
	 */
	public $playerId;
	
	/**
	 * @var int ID of team.
	 */
	public $teamId;
	
	/**
	 * @var int ID of trainer.
	 */
	public $trainerId;
	
	/**
	 * @var reference reference to integer indicating training effect of attribute freshness.
	 */
	public $effectFreshness;
	
	/**
	 * @var reference reference to integer indicating training effect of attribute technique.
	 */
	public $effectTechnique;
	
	/**
	 * @var reference reference to integer indicating training effect of attribute stamina.
	 */
	public $effectStamina;
	
	/**
	 * @var reference reference to integer indicating training effect of attribute satisfaction.
	 */
	public $effectSatisfaction;
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param int $effectFreshness training effect.
	 * @param int $effectTechnique training effect.
	 * @param int $effectStamina training effect.
	 * @param int $effectSatisfaction training effect.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n,
			$playerId, $teamId, $trainerId,
			&$effectFreshness, &$effectTechnique, &$effectStamina, &$effectSatisfaction) {
		parent::__construct($websoccer, $db, $i18n);
		
		$this->playerId = $playerId;
		$this->teamId = $teamId;
		$this->trainerId = $trainerId;
		
		$this->effectFreshness =& $effectFreshness;
		$this->effectTechnique =& $effectTechnique;
		$this->effectStamina =& $effectStamina;
		$this->effectSatisfaction =& $effectSatisfaction;
	}

}

?>
