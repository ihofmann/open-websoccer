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

class BookCampController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		
		$now = $this->_websoccer->getNowAsTimestamp();
		
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $teamId);
		
		// check if duration is in range
		$min = $this->_websoccer->getConfig("trainingcamp_min_days");
		$max = $this->_websoccer->getConfig("trainingcamp_max_days");
		if ($parameters["days"] < $min || $parameters["days"] > $max) {
			throw new Exception(sprintf($this->_i18n->getMessage("trainingcamp_booking_err_invaliddays"), $min, $max));
		}
		
		// check if date is in future
		$startDateObj = DateTime::createFromFormat($this->_websoccer->getConfig("date_format") . " H:i", $parameters["start_date"] . " 00:00");
		$startDateTimestamp = $startDateObj->getTimestamp();
		$endDateTimestamp = $startDateTimestamp + 3600 * 24 * $parameters["days"];
		
		if ($startDateTimestamp <= $now) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_booking_err_dateinpast"));
		}
		
		// check if too far in future
		$maxDate = $now + $this->_websoccer->getConfig("trainingcamp_booking_max_days_in_future") * 3600 * 24;
		if ($startDateTimestamp > $maxDate) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_booking_err_datetoofar", $this->_websoccer->getConfig("trainingcamp_booking_max_days_in_future")));
		}
		
		// get camp details
		$camp = TrainingcampsDataService::getCampById($this->_websoccer, $this->_db, $parameters["id"]);
		if (!$camp) {
			throw new Exception("Illegal ID");
		}
		
		// check if user still has an open training camp
		$existingBookings = TrainingcampsDataService::getCampBookingsByTeam($this->_websoccer, $this->_db, $teamId);
		if (count($existingBookings)) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_booking_err_existingbookings"));
		}
		
		// check if team can afford it.
		$playersOfTeam = PlayersDataService::getPlayersOfTeamById($this->_websoccer, $this->_db, $teamId);
		$totalCosts = $camp["costs"] * $parameters["days"] * count($playersOfTeam);
		if ($totalCosts >= $team["team_budget"]) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_booking_err_tooexpensive"));
		}
		
		// check if there are matches within the time frame
		$matches = MatchesDataService::getMatchesByTeamAndTimeframe($this->_websoccer, $this->_db, $teamId, $startDateTimestamp, $endDateTimestamp);
		if (count($matches)) {
			throw new Exception($this->_i18n->getMessage("trainingcamp_booking_err_matcheswithintimeframe"));
		}
		
		// debit amount
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $teamId,
			$totalCosts,
			"trainingcamp_booking_costs_subject",
			$camp["name"]);
		
		// create camp booking
		$columns["verein_id"] = $teamId;
		$columns["lager_id"] = $camp["id"];
		$columns["datum_start"] = $startDateTimestamp;
		$columns["datum_ende"] = $endDateTimestamp;
		$this->_db->queryInsert($columns, $this->_websoccer->getConfig("db_prefix") . "_trainingslager_belegung");
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("trainingcamp_booking_success"),
				""));
		
		return "trainingcamp";
	}
	
}

?>