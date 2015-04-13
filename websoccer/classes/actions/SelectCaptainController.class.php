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
 * Nominates selected player as tram captain.
 */
class SelectCaptainController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		$team = TeamsDataService::getTeamById($this->_websoccer, $this->_db, $clubId);
		
		// check if it is own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $parameters["id"]);
		if ($clubId != $player["team_id"]) {
			throw new Exception("nice try");
		}
		
		$this->_db->queryUpdate(array("captain_id" => $parameters["id"]), 
				$this->_websoccer->getConfig("db_prefix") . "_verein", "id = %d", $clubId);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("myteam_player_select_as_captain_success"),
				""));
		
		// check if captain has been changed and show disappointment
		if ($team["captain_id"] && $team["captain_id"] != $parameters["id"]) {
			
			$oldPlayer = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $team["captain_id"]);
			
			// maybe player has moved to new team, then just ignore it
			if ($oldPlayer["team_id"] == $clubId) {
				
				$newSatisfaction = round($oldPlayer["player_strength_satisfaction"] * 0.6);
				$this->_db->queryUpdate(array("w_zufriedenheit" => $newSatisfaction),
						$this->_websoccer->getConfig("db_prefix") . "_spieler", "id = %d", $oldPlayer["player_id"]);
				
				$playername = (strlen($oldPlayer["player_pseudonym"])) ? $oldPlayer["player_pseudonym"] : $oldPlayer["player_firstname"] . " " . $oldPlayer["player_lastname"];
				
				$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_WARNING,
						$this->_i18n->getMessage("myteam_player_select_as_captain_warning_old_captain", $playername),
						""));
			}
		}
		
		return null;
	}
	
}

?>