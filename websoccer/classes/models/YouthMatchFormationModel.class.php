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
 * Backing the formation form for a concrete youth match.
 */
class YouthMatchFormationModel implements IModel {
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
		
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		
		// next match
		$matchinfo = YouthMatchesDataService::getYouthMatchinfoById($this->_websoccer, $this->_db, $this->_i18n, 
				$this->_websoccer->getRequestParameter("matchid"));
		
		// check if home or guest team (or else it is an invalid match)
		if ($matchinfo["home_team_id"] == $clubId) {
			$teamPrefix = "home";
		} elseif ($matchinfo["guest_team_id"] == $clubId) {
			$teamPrefix = "guest";
		} else {
			// ID has been entered manually, hence message not important
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		// check if expired
		if ($matchinfo["matchdate"] <= $this->_websoccer->getNowAsTimestamp() || $matchinfo["simulated"]) {
			throw new Exception($this->_i18n->getMessage("youthformation_err_matchexpired"));
		}
		
		// get team players
		$players = null;
		if ($clubId > 0) {
			$players = YouthPlayersDataService::getYouthPlayersOfTeamByPosition($this->_websoccer, $this->_db, $clubId, "DESC");
		}
		
		// get previously saved formation and tactic
		$formation = $this->_getFormation($teamPrefix, $matchinfo);
		
		// override by request parameters
		for ($benchNo = 1; $benchNo <= 5; $benchNo++) {
			if ($this->_websoccer->getRequestParameter("bench" . $benchNo)) {
				$formation["bench" . $benchNo] = $this->_websoccer->getRequestParameter("bench" . $benchNo);
			} else if (!isset($formation["bench" . $benchNo])) {
				$formation["bench" . $benchNo] = "";
			}
		}
		
		$setup = $this->getFormationSetup($formation);
		
		for ($playerNo = 1; $playerNo <= 11; $playerNo++) {
			// set player from request
			if ($this->_websoccer->getRequestParameter("player" . $playerNo)) {
				$formation["player" . $playerNo] = $this->_websoccer->getRequestParameter("player" . $playerNo);
				$formation["player" . $playerNo . "_pos"] = $this->_websoccer->getRequestParameter("player" . $playerNo . "_pos");
				
				// set to 0 if no previous formation is available
			} else if (!isset($formation["player" . $playerNo])) {
				$formation["player" . $playerNo] = "";
				$formation["player" . $playerNo . "_pos"] = "";
			}
		}
		
		return array("matchinfo" => $matchinfo, "players" => $players, 
				"formation" => $formation, "setup" => $setup, "youthFormation" => TRUE);
	}
	
	private function getFormationSetup($formation) {
		
		// default setup
		$setup = array("defense" => 4,
						"dm" => 1,
						"midfield" => 3,
						"om" => 1,
						"striker" => 1);
		
		// override by user input
		if ($this->_websoccer->getRequestParameter("formation_defense") !== NULL) {
			$setup["defense"] = (int) $this->_websoccer->getRequestParameter("formation_defense");
			$setup["dm"] = (int) $this->_websoccer->getRequestParameter("formation_defensemidfield");
			$setup["midfield"] = (int) $this->_websoccer->getRequestParameter("formation_midfield");
			$setup["om"] = (int) $this->_websoccer->getRequestParameter("formation_offensivemidfield");
			$setup["striker"] = (int) $this->_websoccer->getRequestParameter("formation_forward");
			
			// override by request when page is re-loaded after submitting the main form
		} elseif ($this->_websoccer->getRequestParameter("setup") !== NULL) {
			
			$setupParts = explode("-", $this->_websoccer->getRequestParameter("setup"));
				
			$setup["defense"] = (int) $setupParts[0];
			$setup["dm"] = (int) $setupParts[1];
			$setup["midfield"] = (int) $setupParts[2];
			$setup["om"] = (int) $setupParts[3];
			$setup["striker"] = (int) $setupParts[4];
			
			// override by previously saved formation
		} else if (isset($formation["setup"]) && strlen($formation["setup"])) {
			$setupParts = explode("-", $formation["setup"]);
			
			$setup["defense"] = (int) $setupParts[0];
			$setup["dm"] = (int) $setupParts[1];
			$setup["midfield"] = (int) $setupParts[2];
			$setup["om"] = (int) $setupParts[3];
			$setup["striker"] = (int) $setupParts[4];
		}
		
		// alter setup if invalid
		$altered = FALSE;
		while (($noOfPlayers = $setup["defense"] + $setup["dm"] + $setup["midfield"] + $setup["om"] + $setup["striker"]) != 10) {
			
			// reduce players
			if ($noOfPlayers > 10) {
				
				if ($setup["striker"] > 1) {
					$setup["striker"] = $setup["striker"] - 1;
				} elseif ($setup["om"] > 1) {
					$setup["om"] = $setup["om"] - 1;
				} elseif ($setup["dm"] > 1) {
					$setup["dm"] = $setup["dm"] - 1;
				} elseif ($setup["midfield"] > 2) {
					$setup["midfield"] = $setup["midfield"] - 1;
				} else {
					$setup["defense"] = $setup["defense"] - 1;
				}
				
				// increase
			} else {
				
				if ($setup["defense"] < 4) {
					$setup["defense"] = $setup["defense"] + 1;
				} else if ($setup["midfield"] < 4) {
					$setup["midfield"] = $setup["midfield"] + 1;
				} else if ($setup["dm"] < 2) {
					$setup["dm"] = $setup["dm"] + 1;
				} else if ($setup["om"] < 2) {
					$setup["om"] = $setup["om"] + 1;
				} else {
					$setup["striker"] = $setup["striker"] + 1;
				}
			}
			
			$altered = TRUE;
		}
		
		if ($altered) {
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING, 
					$this->_i18n->getMessage("formation_setup_altered_warn_title"), 
					$this->_i18n->getMessage("formation_setup_altered_warn_details")));
		}
		
		return $setup;
	}
	
	private function _getFormation($teamPrefix, $matchinfo) {
		$formation = array();
		
		// substitutions
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			if ($this->_websoccer->getRequestParameter("sub" . $subNo ."_out")) {
				$formation["sub" . $subNo ."_out"] = $this->_websoccer->getRequestParameter("sub" . $subNo ."_out");
				$formation["sub" . $subNo ."_in"] = $this->_websoccer->getRequestParameter("sub" . $subNo ."_in");
				$formation["sub" . $subNo ."_minute"] = $this->_websoccer->getRequestParameter("sub" . $subNo ."_minute");
				$formation["sub" . $subNo ."_condition"] = $this->_websoccer->getRequestParameter("sub" . $subNo ."_condition");
				$formation["sub" . $subNo ."_position"] = $this->_websoccer->getRequestParameter("sub" . $subNo ."_position");
			} else {
				$formation["sub" . $subNo ."_out"] = $matchinfo[$teamPrefix . "_s" . $subNo ."_out"];
				$formation["sub" . $subNo ."_in"] = $matchinfo[$teamPrefix . "_s" . $subNo ."_in"];
				$formation["sub" . $subNo ."_minute"] = $matchinfo[$teamPrefix . "_s" . $subNo ."_minute"];
				$formation["sub" . $subNo ."_condition"] = $matchinfo[$teamPrefix . "_s" . $subNo ."_condition"];
				$formation["sub" . $subNo ."_position"] = $matchinfo[$teamPrefix . "_s" . $subNo ."_position"];
			}
		}
		
		// query already set players and count them for setup
		$setup = array("defense" => 0,
				"dm" => 0,
				"midfield" => 0,
				"om" => 0,
				"striker" => 0);
		$result = $this->_db->querySelect("*", $this->_websoccer->getConfig("db_prefix") . "_youthmatch_player", 
				"match_id = %d AND team_id = %d", array($matchinfo["id"], $matchinfo[$teamPrefix . "_team_id"]));
		while ($player = $result->fetch_array()) {
			if ($player["state"] == "Ersatzbank") {
				$formation["bench" . $player["playernumber"]] = $player["player_id"];
			} else {
				$formation["player" . $player["playernumber"]] = $player["player_id"];
				$formation["player" . $player["playernumber"] . "_pos"] = $player["position_main"];
				
				// increase setup
				$mainPosition = $player["position_main"];
				$position = $player["position"];
				
				if ($position == "Abwehr") {
					$setup["defense"] = $setup["defense"] + 1;
				} elseif ($position == "Sturm") {
					$setup["striker"] = $setup["striker"] + 1;
				} elseif ($position == "Mittelfeld") {
					if ($mainPosition == "DM") {
						$setup["dm"] = $setup["dm"] + 1;
					} elseif ($mainPosition == "OM") {
						$setup["om"] = $setup["om"] + 1;
					} else {
						$setup["midfield"] = $setup["midfield"] + 1;
					}
				}
			}
			
		}
		$result->free();
		
		$setPlayers = $setup["defense"] + $setup["striker"] + $setup["dm"] + $setup["om"] + $setup["midfield"];
		if ($setPlayers > 0) {
			$formation["setup"] = $setup["defense"] . "-" . $setup["dm"] . "-" . $setup["midfield"] . "-" . $setup["om"] . "-" . $setup["striker"];
		}
		return $formation;
	}
	
}

?>