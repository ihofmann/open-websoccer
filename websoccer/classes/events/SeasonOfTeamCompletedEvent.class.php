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
 * This event is triggered when a season is marked as completed for a club.
 * 
 */
class SeasonOfTeamCompletedEvent extends AbstractEvent {
	
	/**
	 * @var int ID of team.
	 */
	public $teamId;
	
	/**
	 * @var int ID of season.
	 */
	public $seasonId;
	
	/**
	 * @var int Team's table position at the end of the season.
	 */
	public $rank;
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param SimulationPlayer $player player data model.
	 * @param int $teamId ID of team.
	 * @param int $seasonId ID of season.
	 * @param int $rank Team's table position at the end of the season.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $teamId, $seasonId, $rank) {
		parent::__construct($websoccer, $db, $i18n);
		
		$this->teamId = $teamId;
		$this->seasonId = $seasonId;
		$this->rank = $rank;
	}

}

?>
