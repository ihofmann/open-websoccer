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
 * This event is triggered when a new youth player got created due to a scouting execution.
 */
class YouthPlayerScoutedEvent extends AbstractEvent {
	
	/**
	 * @var int ID of team.
	 */
	public $teamId;
	
	/**
	 * @var int ID of scout.
	 */
	public $scoutId;
	
	/**
	 * @var int ID of created youth player.
	 */
	public $playerId;
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param int $teamId ID of team.
	 * @param int $scoutId ID of scout.
	 * @param int $playerId ID of created youth player.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n,
			$teamId, $scoutId, $playerId) {
		parent::__construct($websoccer, $db, $i18n);
		
		$this->teamId = $teamId;
		$this->scoutId = $scoutId;
		$this->playerId = $playerId;
	}

}

?>
