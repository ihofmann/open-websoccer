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
class NextMatchModel implements IModel {
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
		if ($this->_websoccer->getRequestParameter("nationalteam")) {
			$clubId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		} else {
			$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		}
		
		$matchinfo = MatchesDataService::getNextMatch($this->_websoccer, $this->_db, $clubId);
		
		// get previous matches against this team
		if (count($matchinfo)) {
			$matchinfo["previous_matches"] = MatchesDataService::getPreviousMatches($matchinfo, $this->_websoccer, $this->_db);
		}
		
		return $matchinfo;
	}
	
}

?>