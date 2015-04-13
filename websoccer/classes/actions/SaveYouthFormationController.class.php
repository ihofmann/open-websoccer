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
 * Saves the formation and its setup in a DB table.
 * Data structure differs from normal matches, hence we need to re-implement it a bit differently.
 */
class SaveYouthFormationController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	private $_addedPlayers;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
		$this->_addedPlayers = array();
	}
	
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		$teamId = $user->getClubId($this->_websoccer, $this->_db);
		
		// next match
		$matchinfo = YouthMatchesDataService::getYouthMatchinfoById($this->_websoccer, $this->_db, $this->_i18n, 
				$parameters["matchid"]);
		
		// check if home or guest team (or else it is an invalid match)
		if ($matchinfo["home_team_id"] == $teamId) {
			$teamPrefix = "home";
		} elseif ($matchinfo["guest_team_id"] == $teamId) {
			$teamPrefix = "guest";
		} else {
			// ID has been entered manually, hence message not important
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		// check if expired
		if ($matchinfo["matchdate"] < $this->_websoccer->getNowAsTimestamp() || $matchinfo["simulated"]) {
			throw new Exception($this->_i18n->getMessage("youthformation_err_matchexpired"));
		}
		
		// get team players and check whether provided IDs are valid players (ceck for duplicate players only, for now)
		$this->validatePlayer($parameters["player1"]);
		$this->validatePlayer($parameters["player2"]);
		$this->validatePlayer($parameters["player3"]);
		$this->validatePlayer($parameters["player4"]);
		$this->validatePlayer($parameters["player5"]);
		$this->validatePlayer($parameters["player6"]);
		$this->validatePlayer($parameters["player7"]);
		$this->validatePlayer($parameters["player8"]);
		$this->validatePlayer($parameters["player9"]);
		$this->validatePlayer($parameters["player10"]);
		$this->validatePlayer($parameters["player11"]);
		
		$this->validatePlayer($parameters["bench1"]);
		$this->validatePlayer($parameters["bench2"]);
		$this->validatePlayer($parameters["bench3"]);
		$this->validatePlayer($parameters["bench4"]);
		$this->validatePlayer($parameters["bench5"]);
		
		// validate substitutions
		$validSubstitutions = array();
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			$playerIn = $parameters["sub" . $subNo ."_in"];
			$playerOut = $parameters["sub" . $subNo ."_out"];
			$playerMinute = $parameters["sub" . $subNo ."_minute"];
			if ($playerIn != null && $playerIn > 0 && $playerOut != null && $playerOut > 0 && $playerMinute != null && $playerMinute > 0) {
				$this->validateSubstitution($playerIn, $playerOut, $playerMinute);
				$validSubstitutions[] = $subNo;
			}
		}
		
		// save formation
		$this->saveFormation($teamId, $parameters, $validSubstitutions, $matchinfo, $teamPrefix);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("saved_message_title"),
				""));
		
		return null;
	}
	
	private function validatePlayer($playerId) {
		if ($playerId == null || $playerId == 0) {
			return;
		}
		
		if (isset($this->_addedPlayers[$playerId])) {
			throw new Exception($this->_i18n->getMessage("formation_err_duplicateplayer"));
		}
		
		$this->_addedPlayers[$playerId] = TRUE;
	}
	
	private function validateSubstitution($playerIn, $playerOut, $minute) {
		
		if (!isset($this->_addedPlayers[$playerIn]) || !isset($this->_addedPlayers[$playerOut])) {
			throw new Exception($this->_i18n->getMessage("formation_err_invalidplayer"));
		}
		
		if ($minute < 1 || $minute > 90) {
			throw new Exception($this->_i18n->getMessage("formation_err_invalidsubstitutionminute"));
		}
		
	}
	
	private function saveFormation($teamId, $parameters, $validSubstitutions, $matchinfo, $teamPrefix) {
		// delete old formation
		$this->_db->queryDelete($this->_websoccer->getConfig("db_prefix") ."_youthmatch_player", "match_id = %d AND team_id = %d",
				 array($parameters["matchid"], $teamId));
		
		// define mapping of player number and actual main position on field
		$setupParts = explode("-",  $parameters["setup"]);
		if (count($setupParts) != 5) {
			throw new Exception("illegal formation setup");
		}
		
// 		$mainPositionMapping = array(1 => "T");
		
// 		// defense
// 		if ($setupParts[0] == 1) {
// 			$mainPositionMapping[2] = "IV";
// 		} elseif ($setupParts[0] == 2) {
// 			$mainPositionMapping[2] = "IV";
// 			$mainPositionMapping[3] = "IV";
// 		} elseif ($setupParts[0] == 3) {
// 			$mainPositionMapping[2] = "LV";
// 			$mainPositionMapping[3] = "IV";
// 			$mainPositionMapping[4] = "RV";
// 		} else {
// 			$mainPositionMapping[2] = "LV";
// 			$mainPositionMapping[3] = "IV";
// 			$mainPositionMapping[4] = "IV";
// 			$mainPositionMapping[5] = "RV";
				
// 			$setupParts[0] = 4; // set a valid number in case an invalid one had been set
// 		}
		
// 		// defensive midfield
// 		if ($setupParts[1] == 1) {
// 			$mainPositionMapping[$setupParts[0] + 2] = "DM";
// 		} elseif ($setupParts[1] == 2) {
// 			$mainPositionMapping[$setupParts[0] + 2] = "DM";
// 			$mainPositionMapping[$setupParts[0] + 3] = "DM";
// 		} else {
// 			$setupParts[1] = 0;
// 		}
		
// 		// midfield
// 		if ($setupParts[2] == 1) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 2] = "ZM";
// 		} elseif ($setupParts[2] == 2) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 2] = "LM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 3] = "RM";
// 		} elseif ($setupParts[2] == 3) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 2] = "LM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 3] = "ZM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 4] = "RM";
// 		} elseif ($setupParts[2] == 4) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 2] = "LM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 3] = "ZM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 4] = "ZM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + 5] = "RM";
// 		} else {
// 			$setupParts[2] = 0;
// 		}
		
// 		// offensive midfield
// 		if ($setupParts[3] == 1) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + 2] = "OM";
// 		} elseif ($setupParts[3] == 2) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + 2] = "OM";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + 3] = "OM";
// 		} else {
// 			$setupParts[3] = 0;
// 		}
		
// 		// striker
// 		if ($setupParts[4] == 1) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + $setupParts[3] + 2] = "MS";
// 		} elseif ($setupParts[4] == 2) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + $setupParts[3] + 2] = "MS";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + $setupParts[3] + 3] = "MS";
// 		} elseif ($setupParts[4] == 3) {
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + $setupParts[3] + 2] = "LS";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + $setupParts[3] + 3] = "MS";
// 			$mainPositionMapping[$setupParts[0] + $setupParts[1] + $setupParts[2] + $setupParts[3] + 4] = "RS";
// 		}
		
		$positionMapping = array(
				"T" => "Torwart",
				"LV" => "Abwehr",
				"IV" => "Abwehr",
				"RV" => "Abwehr",
				"DM" => "Mittelfeld",
				"OM" => "Mittelfeld",
				"ZM" => "Mittelfeld",
				"LM" => "Mittelfeld",
				"RM" => "Mittelfeld",
				"LS" => "Sturm",
				"MS" => "Sturm",
				"RS" => "Sturm"
		);
		
		// create field players
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			$mainPosition = $this->_websoccer->getRequestParameter("player" . $playerNo . "_pos");
			$position = $positionMapping[$mainPosition];
			$this->savePlayer($parameters["matchid"], $teamId, $parameters["player" . $playerNo], $playerNo, $position, $mainPosition, FALSE);
		}
		
		// create bench players
		for ($playerNo = 1; $playerNo <= 5; $playerNo++) {
			if ($parameters["bench" . $playerNo]) {
				$this->savePlayer($parameters["matchid"], $teamId, $parameters["bench" . $playerNo], $playerNo, "-", "-", TRUE);
			}
		}
		
		// save substitutions
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_youthmatch";
		$columns = array();
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if (in_array($subNo, $validSubstitutions)) {
				$columns[$teamPrefix . "_s". $subNo . "_out"] = $parameters["sub" . $subNo ."_out"];
				$columns[$teamPrefix . "_s". $subNo . "_in"] = $parameters["sub" . $subNo ."_in"];
				$columns[$teamPrefix . "_s". $subNo . "_minute"] = $parameters["sub" . $subNo ."_minute"];
				$columns[$teamPrefix . "_s". $subNo . "_condition"] = $parameters["sub" . $subNo ."_condition"];
				$columns[$teamPrefix . "_s". $subNo . "_position"] = $this->_websoccer->getRequestParameter("sub" . $subNo ."_position");
			} else {
				$columns[$teamPrefix . "_s". $subNo . "_out"] = "";
				$columns[$teamPrefix . "_s". $subNo . "_in"] = "";
				$columns[$teamPrefix . "_s". $subNo . "_minute"] = "";
				$columns[$teamPrefix . "_s". $subNo . "_condition"] = "";
				$columns[$teamPrefix . "_s". $subNo . "_position"] = "";
			}
		}
		
		// update match table
		$this->_db->queryUpdate($columns, $fromTable, "id = %d", $parameters["matchid"]);
	}
	
	private function savePlayer($matchId, $teamId, $playerId, $playerNumber, $position, $mainPosition, $onBench) {
		$columns = array(
				"match_id" => $matchId,
				"team_id" => $teamId,
				"player_id" => $playerId,
				"playernumber" => $playerNumber,
				"position" => $position,
				"position_main" => $mainPosition,
				"state" => ($onBench) ? "Ersatzbank" : "1",
				"strength" => 0,
				"name" => $playerId
				);
		
		$this->_db->queryInsert($columns, $this->_websoccer->getConfig("db_prefix") ."_youthmatch_player");
	}
	
}

?>