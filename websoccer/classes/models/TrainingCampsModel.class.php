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
 * @author Ingo Hofmann
 */
class TrainingCampsModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return TRUE;
	}
	
	public function getTemplateParameters() {
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		$camps = array();
		$bookedCamp = array();
		
		$bookedCamps = TrainingcampsDataService::getCampBookingsByTeam($this->_websoccer, $this->_db, $teamId);
		
		$listCamps = TRUE;
		if (count($bookedCamps)) {
			
			$bookedCamp = $bookedCamps[0];
			if ($bookedCamp["date_end"] < $this->_websoccer->getNowAsTimestamp()) {
				TrainingcampsDataService::executeCamp($this->_websoccer, $this->_db, $teamId, $bookedCamp);
				$bookedCamp = array();
			} else {
				$listCamps = FALSE;
			}
			
		}
		
		// provide camps to book
		if ($listCamps) {
			$camps = TrainingcampsDataService::getCamps($this->_websoccer, $this->_db);
		}
		
		return array("bookedCamp" => $bookedCamp, "camps" => $camps);
	}
	
}

?>