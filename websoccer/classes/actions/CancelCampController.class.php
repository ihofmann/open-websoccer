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

class CancelCampController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		$existingBookings = TrainingcampsDataService::getCampBookingsByTeam($this->_websoccer, $this->_db, $teamId);
		if (!count($existingBookings)) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_cancel_illegalid"));
		}
		
		$deleted = FALSE;
		foreach ($existingBookings as $booking) {
			if ($booking["id"] == $parameters["bookingid"]) {
				$this->_db->queryDelete($this->_websoccer->getConfig("db_prefix") . "_trainingslager_belegung", "id = %d", $booking["id"]);
				$deleted = TRUE;
			}
		}
			
		if (!$deleted) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_cancel_illegalid"));
		}
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("trainingcamp_cancel_success"),
				""));
		
		return "trainingcamp";
	}
	
}

?>