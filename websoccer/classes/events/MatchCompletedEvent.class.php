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
 * Event triggered when a match (no matter if regular, friendly or youth match) is completed. All other evend handlers
 * have been called before.
 * DO NOT FORGET TO CHECK WHETHER MATCH IS A YOUTH MATCH OR NOT!
 */
class MatchCompletedEvent extends AbstractEvent {
	
	/**
	 * @var SimulationMatch Data model of completed match.
	 */
	public $match;
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param SimulationMatch $match Match data model.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n,
			SimulationMatch $match) {
		parent::__construct($websoccer, $db, $i18n);
		
		$this->match = $match;
	}

}

?>
