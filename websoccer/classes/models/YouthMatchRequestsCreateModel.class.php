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
 * Provides data for time selection and other elements for creating a new youth match request.
 */
class YouthMatchRequestsCreateModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return $this->_websoccer->getConfig("youth_enabled");
	}
	
	public function getTemplateParameters() {
		
		$timeOptions = array();
		
		$maxDays = $this->_websoccer->getConfig("youth_matchrequest_max_futuredays");
		
		$times = explode(",", $this->_websoccer->getConfig("youth_matchrequest_allowedtimes"));
		$validTimes = array();
		foreach($times as $time) {
			$validTimes[] = explode(":", $time);
		}
		
		$dateOptions = array();
		
		$dateObj = new DateTime();
		$dateFormat = $this->_websoccer->getConfig("datetime_format");
		for ($day = 1; $day <= $maxDays; $day++) {
			
			$dateObj->add(new DateInterval('P1D'));
			
			foreach ($validTimes as $validTime) {
				$hour = $validTime[0];
				$minute = $validTime[1];
				
				$dateObj->setTime($hour, $minute);
				
				$dateOptions[$dateObj->getTimestamp()] = $dateObj->format($dateFormat);
			}
			
		}
		
		return array("dateOptions" => $dateOptions);
	}
	
}

?>