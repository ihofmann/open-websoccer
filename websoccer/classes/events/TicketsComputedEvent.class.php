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
 * This event is triggered when a the ticket sold rate is computed during a match simulation, just before the 
 * the revenue is saved in DB.
 */
class TicketsComputedEvent extends AbstractEvent {
	
	/**
	 * @var SimulationMatch Match data model.
	 */
	public $match;
	
	/**
	 * @var int ID of stadium.
	 */
	public $stadiumId;
	
	/**
	 * @var reference reference to float number indicating to which extend the tickets for stands are sold out.
	 */
	public $rateStands;
	
	/**
	 * @var reference reference to float number indicating to which extend the tickets for seats are sold out.
	 */
	public $rateSeats;
	
	/**
	 * @var reference reference to float number indicating to which extend the tickets for stands (grandstand) are sold out.
	 */
	public $rateStandsGrand;
	
	/**
	 * @var reference reference to float number indicating to which extend the tickets for seats (grandstand) are sold out.
	 */
	public $rateSeatsGrand;
	
	/**
	 * @var reference reference to float number indicating to which extend the tickets for VIP lounged are sold out.
	 */
	public $rateVip;
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param I18n $i18n Messages context.
	 * @param SimulationMatch $match Match data model.
	 * @param int $stadiumId ID of stadium.
	 * @param float $rateStands sales rate.
	 * @param float $rateSeats sales rate.
	 * @param float $rateStandsGrand sales rate.
	 * @param float $rateSeatsGrand sales rate.
	 * @param float $rateVip sales rate.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n,
			SimulationMatch $match, $stadiumId,
			&$rateStands, &$rateSeats, &$rateStandsGrand, &$rateSeatsGrand, &$rateVip) {
		parent::__construct($websoccer, $db, $i18n);
		
		$this->match = $match;
		$this->stadiumId = $stadiumId;
		
		$this->rateStands =& $rateStands;
		$this->rateSeats =& $rateSeats;
		$this->rateStandsGrand =& $rateStandsGrand;
		$this->rateSeatsGrand =& $rateSeatsGrand;
		$this->rateVip =& $rateVip;
	}

}

?>
