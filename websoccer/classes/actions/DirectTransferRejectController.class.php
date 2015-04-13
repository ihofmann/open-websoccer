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
 * Cancels user's own direct transfer.
 * 
 * @author Ingo Hofmann
 */
class DirectTransferRejectController implements IActionController {
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
		if (!$this->_websoccer->getConfig("transferoffers_enabled")) {
			return;
		}
		
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		
		
		// get offer information
		$result = $this->_db->querySelect("*", $this->_websoccer->getConfig("db_prefix") . "_transfer_offer", 
				"id = %d AND receiver_club_id = %d",
				array($parameters["id"], $clubId));
		$offer = $result->fetch_array();
		$result->free();
		
		if (!$offer) {
			throw new Exception($this->_i18n->getMessage("transferoffers_offer_cancellation_notfound"));
		}
		
		$this->_db->queryUpdate(array(
					"rejected_date" => $this->_websoccer->getNowAsTimestamp(),
					"rejected_message" => $parameters["comment"],
					"rejected_allow_alternative" => ($parameters["allow_alternative"]) ? 1 : 0
				), $this->_websoccer->getConfig("db_prefix") . "_transfer_offer", "id = %d", $offer["id"]);
		
		// get player name for notification
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $offer["player_id"]);
		if ($player["player_pseudonym"]) {
			$playerName = $player["player_pseudonym"];
		} else {
			$playerName = $player["player_firstname"] . " " . $player["player_lastname"];
		}
		
		// create notification
		NotificationsDataService::createNotification($this->_websoccer, $this->_db, $offer["sender_user_id"], "transferoffer_notification_rejected",
			array("playername" => $playerName, "receivername" => $this->_websoccer->getUser()->username), "transferoffer", "transferoffers#sent");
			
		// show success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("transferoffers_offer_reject_success"),
				""));
		
		return null;
	}
	
}

?>