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
define("NAMES_DIRECTORY", BASE_FOLDER . "/admin/config/names");

/**
 * Creates a new youth players if scouting is successful.
 */
class ScoutYouthPlayerController implements IActionController {
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
		if (!$this->_websoccer->getConfig("youth_enabled") && $this->_websoccer->getConfig("youth_scouting_enabled")) {
			return NULL;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if user has a club
		if ($clubId < 1) {
			throw new Exception($this->_i18n->getMessage("error_action_required_team"));
		}
		
		// check if break is violated
		$lastExecutionTimestamp = YouthPlayersDataService::getLastScoutingExecutionTime($this->_websoccer, $this->_db,
				$this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db));
		$nextPossibleExecutionTimestamp = $lastExecutionTimestamp + $this->_websoccer->getConfig("youth_scouting_break_hours") * 3600;
		$now = $this->_websoccer->getNowAsTimestamp();
		
		if ($now < $nextPossibleExecutionTimestamp) {
			throw new Exception($this->_i18n->getMessage("youthteam_scouting_err_breakviolation",
					$this->_websoccer->getFormattedDatetime($nextPossibleExecutionTimestamp)));
		}
		
		// check if valid country (if name files exists)
		$namesFolder = NAMES_DIRECTORY . "/" . $parameters["country"];
		if (!file_exists($namesFolder . "/firstnames.txt") || !file_exists($namesFolder . "/lastnames.txt")) {
			throw new Exception($this->_i18n->getMessage("youthteam_scouting_err_invalidcountry"));
		}
		
		// check if valid scout
		$scout = YouthPlayersDataService::getScoutById($this->_websoccer, $this->_db, $this->_i18n, $parameters["scoutid"]);
		
		// check if team can afford it.
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		if ($team["team_budget"] <= $scout["fee"]) {
			throw new Exception($this->_i18n->getMessage("youthteam_scouting_err_notenoughbudget"));
		}
		
		// deduct fee
		BankAccountDataService::debitAmount($this->_websoccer, $this->_db, $clubId, $scout["fee"], 
			"youthteam_scouting_fee_subject", $scout["name"]);
		
		// has scout found someone?
		$found = TRUE;
		$succesProbability = (int) $this->_websoccer->getConfig("youth_scouting_success_probability");
		if ($this->_websoccer->getConfig("youth_scouting_success_probability") < 100) {
			$found = SimulationHelper::selectItemFromProbabilities(array(
						TRUE => $succesProbability,
						FALSE => 100 - $succesProbability
					));
		}
		
		// he found someone, so create youth player
		if ($found) {
			$this->createYouthPlayer($clubId, $scout, $parameters["country"]);
			
			// create failure message
		} else {
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING,
					$this->_i18n->getMessage("youthteam_scouting_failure"),
					""));
		}
		
		// update last execution time
		$this->_db->queryUpdate(array("scouting_last_execution" => $now), 
				$this->_websoccer->getConfig("db_prefix") . "_verein", "id = %d", $clubId);
		
		
		return ($found) ? "youth-team" : "youth-scouting";
	}
	
	private function createYouthPlayer($clubId, $scout, $country) {
		
		$firstName = $this->getItemFromFile(NAMES_DIRECTORY . "/" . $country . "/firstnames.txt");
		$lastName = $this->getItemFromFile(NAMES_DIRECTORY . "/" . $country . "/lastnames.txt");
		
		// strength computation (always compute since plug-ins might override scouting-success-flag)
		$minStrength = (int) $this->_websoccer->getConfig("youth_scouting_min_strength");
		$maxStrength = (int) $this->_websoccer->getConfig("youth_scouting_max_strength");
		$scoutFactor = $scout["expertise"] / 100;
		$strength = $minStrength + round(($maxStrength - $minStrength) * $scoutFactor);
		
		// consider random deviation
		$deviation = (int) $this->_websoccer->getConfig("youth_scouting_standard_deviation");
		$strength = $strength + SimulationHelper::getMagicNumber(0 - $deviation, $deviation);
		$strength = max($minStrength, min($maxStrength, $strength)); // make sure that condigured boundaries are not violated
		
		// determine position
		if ($scout["speciality"] == "Torwart") {
			$positionProbabilities = array(
					"Torwart" => 40,
					"Abwehr" => 30,
					"Mittelfeld" => 25,
					"Sturm" => 5);
		} elseif ($scout["speciality"] == "Abwehr") {
			$positionProbabilities = array(
					"Torwart" => 10,
					"Abwehr" => 50,
					"Mittelfeld" => 30,
					"Sturm" => 10);
		} elseif ($scout["speciality"] == "Mittelfeld") {
			$positionProbabilities = array(
					"Torwart" => 10,
					"Abwehr" => 15,
					"Mittelfeld" => 60,
					"Sturm" => 15);
		} elseif ($scout["speciality"] == "Sturm") {
			$positionProbabilities = array(
					"Torwart" => 5,
					"Abwehr" => 15,
					"Mittelfeld" => 40,
					"Sturm" => 40);
		} else {
			$positionProbabilities = array(
					"Torwart" => 15,
					"Abwehr" => 30,
					"Mittelfeld" => 35,
					"Sturm" => 20);
		}
		
		$position = SimulationHelper::selectItemFromProbabilities($positionProbabilities);
		
		$minAge = $this->_websoccer->getConfig("youth_scouting_min_age");
		$maxAge = $this->_websoccer->getConfig("youth_min_age_professional");
		$age = $minAge + SimulationHelper::getMagicNumber(0, abs($maxAge - $minAge));
		
		// create player
		$this->_db->queryInsert(array(
				"team_id" => $clubId,
				"firstname" => $firstName,
				"lastname" => $lastName,
				"age" => $age,
				"position" => $position,
				"nation" => $country,
				"strength" => $strength
				), $this->_websoccer->getConfig("db_prefix") . "_youthplayer");
		
		// trigger event for plug-ins
		$event = new YouthPlayerScoutedEvent($this->_websoccer, $this->_db, $this->_i18n,
				$clubId, $scout["id"], $this->_db->getLastInsertedId());
		PluginMediator::dispatchEvent($event);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("youthteam_scouting_success"),
				$this->_i18n->getMessage("youthteam_scouting_success_details", $firstName . " " . $lastName)));
		
	}
	
	private function getItemFromFile($fileName) {
		
		$items = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$itemsCount = count($items);
		if (!$itemsCount) {
			throw new Exception($this->_i18n->getMessage("youthteam_scouting_err_invalidcountry"));
		}
	
		return $items[mt_rand(0, $itemsCount - 1)];
	}
	
}

?>