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

class SellPlayerController implements IActionController {
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
		if (!$this->_websoccer->getConfig("transfermarket_enabled")) {
			return NULL;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		
		// check if it is own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $parameters["id"]);
		if ($clubId != $player["team_id"]) {
			throw new Exception("nice try");
		}
		
		// check if player is already on market
		if ($player["player_transfermarket"]) {
			throw new Exception($this->_i18n->getMessage("sell_player_already_on_list"));
		}
		
		// check if player is borrowed or lendable. User should not come to this point, so message is not important.
		if ($player["lending_fee"] > 0) {
			throw new Exception($this->_i18n->getMessage("lending_err_alreadyoffered"));
		}
		
		// check violation of minimum team size
		$teamSize = TeamsDataService::getTeamSize($this->_websoccer, $this->_db, $clubId);
		if ($teamSize <= $this->_websoccer->getConfig("transfermarket_min_teamsize")) {
			throw new Exception($this->_i18n->getMessage("sell_player_teamsize_too_small", $teamSize));
		}
		
		$minBidBoundary = round($player["player_marketvalue"] / 2);
		if ($parameters["min_bid"] < $minBidBoundary) {
			throw new Exception($this->_i18n->getMessage("sell_player_min_bid_too_low"));
		}
		
		$this->updatePlayer($player["player_id"], $parameters["min_bid"]);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("sell_player_success"),
				""));
		
		return "transfermarket";
	}
	
	public function updatePlayer($playerId, $minBid) {
		
		$now = $this->_websoccer->getNowAsTimestamp();
		
		$columns["transfermarkt"] = 1;
		$columns["transfer_start"] = $now;
		$columns["transfer_ende"] = $now + 24 * 3600 * $this->_websoccer->getConfig("transfermarket_duration_days");
		$columns["transfer_mindestgebot"] = $minBid;
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_spieler";
		$whereCondition = "id = %d";
		$parameters = $playerId;
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
	}
	
}

?>