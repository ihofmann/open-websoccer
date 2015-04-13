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
 * This event is triggered when a youth player played at a youth match. Plug-Ins can also override
 * the computed strength change of a youth player.
 */
class YouthPlayerPlayedEvent extends AbstractEvent {
	
	/**
	 * @var SimulationPlayer Data model of youth player including statistics and grade for the current match.
	 */
	public $player;
	
	/**
	 * @var reference Reference to integer which indicates the strength change after the match.
	 */
	public $strengthChange;
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param SimulationPlayer $player player data model.
	 * @param int $strengthChange change in strength after match.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n,
			SimulationPlayer $player, &$strengthChange) {
		parent::__construct($websoccer, $db, $i18n);
		
		$this->player = $player;
		$this->strengthChange =& $strengthChange;
	}

}

?>
