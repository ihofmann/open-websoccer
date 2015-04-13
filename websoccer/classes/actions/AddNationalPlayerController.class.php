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
 * Adds player with passed ID to user's national team.
 */
class AddNationalPlayerController implements IActionController {
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
		// check if feature is enabled
		if (!$this->_websoccer->getConfig("nationalteams_enabled")) {
			return NULL;
		}
		
		// get team info
		$teamId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		if (!$teamId) {
			throw new Exception($this->_i18n->getMessage("nationalteams_user_requires_team"));
		}
		$result = $this->_db->querySelect("name", $this->_websoccer->getConfig("db_prefix") . "_verein", "id = %d", $teamId);
		$team = $result->fetch_array();
		$result->free();
		
		// get player info
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_spieler";
		$result = $this->_db->querySelect("nation", $fromTable, "id = %d", $parameters["id"]);
		$player = $result->fetch_array();
		$result->free();
		
		if (!$player) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		// check if from same nation. If not, user most probably tries to cheat, hence i18n not important.
		if ($player["nation"] != $team["name"]) {
			throw new Exception("Player is from different nation.");
		}
		
		// check if already in team
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_nationalplayer";
		$result = $this->_db->querySelect("COUNT(*) AS hits", $fromTable, "player_id = %d", $parameters["id"]);
		$players = $result->fetch_array();
		$result->free();
		
		if ($players && $players["hits"]) {
			throw new Exception($this->_i18n->getMessage("nationalteams_addplayer_err_alreadyinteam"));
		}
		
		// check maximum number of players
		$result = $this->_db->querySelect("COUNT(*) AS hits", $fromTable, "team_id = %d", $teamId);
		$existingplayers = $result->fetch_array();
		$result->free();
		if ($existingplayers["hits"] >= 30) {
			throw new Exception($this->_i18n->getMessage("nationalteams_addplayer_err_toomanyplayer", 30));
		}
		
		// add to team
		$this->_db->queryInsert(array(
				"team_id" => $teamId,
				"player_id" => $parameters["id"]
				), $fromTable);
		
		// create success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("nationalteams_addplayer_success"),
				""));
		
		return "nationalteam";
	}
	
}

?>