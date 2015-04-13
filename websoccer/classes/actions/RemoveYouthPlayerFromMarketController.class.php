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
 * Removes a youth player from the youth tranfer markets, i.e. sets tranfer fee to 0.
 */
class RemoveYouthPlayerFromMarketController implements IActionController {
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
		if (!$this->_websoccer->getConfig("youth_enabled")) {
			return NULL;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if it is own player
		$player = YouthPlayersDataService::getYouthPlayerById($this->_websoccer, $this->_db, $this->_i18n, $parameters["id"]);
		if ($clubId != $player["team_id"]) {
			throw new Exception($this->_i18n->getMessage("youthteam_err_notownplayer"));
		}
		
		$this->updatePlayer($parameters["id"]);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("youthteam_removefrommarket_success"),
				""));
		
		return "youth-team";
	}
	
	private function updatePlayer($playerId) {
		
		$columns = array("transfer_fee" => 0);
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_youthplayer";
		$whereCondition = "id = %d";
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $playerId);
	}
	
}

?>