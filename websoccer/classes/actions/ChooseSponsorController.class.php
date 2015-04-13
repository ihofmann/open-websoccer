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

class ChooseSponsorController implements IActionController {
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
			return null;
		}
		
		$sponsor = SponsorsDataService::getSponsorinfoByTeamId($this->_websoccer, $this->_db, $teamId);
		
		if ($sponsor) {
			throw new Exception($this->_i18n->getMessage("sponsor_choose_stillcontract"));
		}	
		
		// check min matchday
		$teamMatchday = MatchesDataService::getMatchdayNumberOfTeam($this->_websoccer, $this->_db, $teamId);
		if ($teamMatchday < $this->_websoccer->getConfig("sponsor_earliest_matchday")) {
			throw new Exception($this->_i18n->getMessage("sponsor_choose_tooearly", $this->_websoccer->getConfig("sponsor_earliest_matchday")));
		}
		
		// check if selected sponsor is in list of available sponsors
		// (sponsor might be selected by other teams in meanwhile)
		$sponsors = SponsorsDataService::getSponsorOffers($this->_websoccer, $this->_db, $teamId);
		$found = FALSE;
		foreach ($sponsors as $availableSponsor) {
			if ($availableSponsor["sponsor_id"] == $parameters["id"]) {
				$found = TRUE;
				break;
			}
		}
		
		if (!$found) {
			throw new Exception($this->_i18n->getMessage("sponsor_choose_novalidsponsor"));
		}
		
		// update team
		$columns["sponsor_id"] = $parameters["id"];
		$columns["sponsor_spiele"] = $this->_websoccer->getConfig("sponsor_matches");
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_verein";
		$whereCondition = "id = %d";
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $teamId);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("sponsor_choose_success"),
				""));
		
		return null;
	}
	
}

?>