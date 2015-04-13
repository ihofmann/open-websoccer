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
 * Creates a new youth match request, if allowed.
 */
class CreateYouthMatchRequestController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		// check if feature is enabled
		if (!$this->_websoccer->getConfig("youth_enabled") || !$this->_websoccer->getConfig("youth_matchrequests_enabled")) {
			return NULL;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if user has a club
		if ($clubId < 1) {
			throw new Exception($this->_i18n->getMessage("error_action_required_team"));
		}
		
		// check if date is valid (might be manipulated)
		$tooLateBoundary = $this->_websoccer->getNowAsTimestamp() + 3600 * 24 * (1 + $this->_websoccer->getConfig("youth_matchrequest_max_futuredays"));
		$validTimes = explode(",", $this->_websoccer->getConfig("youth_matchrequest_allowedtimes"));
		
		// check valid times (remove white spaces)
		$timeIsValid = FALSE;
		$matchTime = date("H:i", $parameters["matchdate"]);
		foreach ($validTimes as $validTime) {
			if ($matchTime == trim($validTime)) {
				$timeIsValid = TRUE;
				break;
			}
		}
			
		if (!$timeIsValid || $parameters["matchdate"] > $tooLateBoundary) {
			throw new Exception($this->_i18n->getMessage("youthteam_matchrequest_create_err_invaliddate"));
		}
		
		// check maximum number of open requests
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_youthmatch_request";
		
		$result = $this->_db->querySelect("COUNT(*) AS hits", $fromTable, "team_id = %d", $clubId);
		$requests = $result->fetch_array();
		$result->free();
		
		$maxNoOfRequests = (int) $this->_websoccer->getConfig("youth_matchrequest_max_open_requests");
		if ($requests && $requests["hits"] >= $maxNoOfRequests) {
			throw new Exception($this->_i18n->getMessage("youthteam_matchrequest_create_err_too_many_open_requests", $maxNoOfRequests));
		}
		
		// check if reward can be paid
		if ($parameters["reward"]) {
			$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
			if ($team["team_budget"] <= $parameters["reward"]) {
				throw new Exception($this->_i18n->getMessage("youthteam_matchrequest_create_err_budgetnotenough"));
			}
		}
		
		// check if enough youth players
		if (YouthPlayersDataService::countYouthPlayersOfTeam($this->_websoccer, $this->_db, $clubId) < 11) {
			throw new Exception($this->_i18n->getMessage("youthteam_matchrequest_create_err_notenoughplayers"));
		}
		
		// check maximum number of matches per day constraint
		$maxMatchesPerDay = $this->_websoccer->getConfig("youth_match_maxperday");
		if (YouthMatchesDataService::countMatchesOfTeamOnSameDay($this->_websoccer, $this->_db, $clubId, $parameters["matchdate"]) >= $maxMatchesPerDay) {
			throw new Exception($this->_i18n->getMessage("youthteam_matchrequest_err_maxperday_violated", $maxMatchesPerDay));
		}
		
		// create request
		$columns = array(
				"team_id" => $clubId,
				"matchdate" => $parameters["matchdate"],
				"reward" => $parameters["reward"]
				);
		$this->_db->queryInsert($columns, $fromTable);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("youthteam_matchrequest_create_success"),
				""));
		
		return "youth-matchrequests";
	}
	
}

?>