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
define("NOTIFICATION_TYPE", "transferoffer");
define("NOTIFICATION_TARGETPAGE", "transferoffers");

/**
 * Data service for processing direct transfers. Direct transfers are player transfers which are agreed outside of the
 * official transfer market.
 */
class DirectTransfersDataService {
	
	/**
	 * Creates a new direct transfer offer and notifications.
	 * 
	 * @param WebSoccer $websoccer Application context
	 * @param DbConnection $db DB connection.
	 * @param int $playerId ID of player to transfer.
	 * @param int $senderUserId ID of user who made the offer.
	 * @param int $senderClubId ID of user's team.
	 * @param int $receiverUserId ID of player's manager.
	 * @param int $receiverClubId ID of player's team.
	 * @param int $offerAmount amount to offer.
	 * @param string $offerMessage optional message from user.
	 * @param int $offerPlayerId1 optional ID of an exchange player.
	 * @param int $offerPlayerId2 another optional ID of an exchange player.
	 */
	public static function createTransferOffer(WebSoccer $websoccer, DbConnection $db, $playerId, 
			$senderUserId, $senderClubId, $receiverUserId, $receiverClubId,
			$offerAmount, $offerMessage, $offerPlayerId1 = null, $offerPlayerId2 = null) {
		
		$columns = array(
				"player_id" => $playerId,
				"sender_user_id" => $senderUserId,
				"sender_club_id" => $senderClubId,
				"receiver_club_id" => $receiverClubId,
				"submitted_date" => $websoccer->getNowAsTimestamp(),
				"offer_amount" => $offerAmount,
				"offer_message" => $offerMessage,
				"offer_player1" => $offerPlayerId1,
				"offer_player2" => $offerPlayerId2
				);
		
		$db->queryInsert($columns, $websoccer->getConfig("db_prefix") . "_transfer_offer");
		
		$sender = UsersDataService::getUserById($websoccer, $db, $senderUserId);
		
		// create notification
		NotificationsDataService::createNotification($websoccer, $db, $receiverUserId, "transferoffer_notification_offerreceived",
			array("sendername" => $sender["nick"]), NOTIFICATION_TYPE, NOTIFICATION_TARGETPAGE, null, $receiverClubId);
		
	}
	
	/**
	 * Executes a transfer according to direct transfer offer. Deletes all offers for this player on success.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param unknown $offerId id of direct transfer offer.
	 */
	public static function executeTransferFromOffer(WebSoccer $websoccer, DbConnection $db, $offerId) {
		
		// offer data
		$result = $db->querySelect("*", $websoccer->getConfig("db_prefix") . "_transfer_offer", "id = %d", $offerId);
		$offer = $result->fetch_array();
		$result->free();
		
		if (!$offer) {
			return;
		}
		
		$currentTeam = TeamsDataService::getTeamSummaryById($websoccer, $db, $offer["receiver_club_id"]);
		$targetTeam = TeamsDataService::getTeamSummaryById($websoccer, $db, $offer["sender_club_id"]);
		
		// move player (and create transfer log)
		self::_transferPlayer($websoccer, $db, $offer["player_id"], $offer["sender_club_id"], $offer["sender_user_id"], 
				$currentTeam["user_id"], $offer["receiver_club_id"], $offer["offer_amount"], $offer["offer_player1"], $offer["offer_player2"]);
		
		// credit amount
		BankAccountDataService::creditAmount($websoccer, $db, $offer["receiver_club_id"], $offer["offer_amount"], "directtransfer_subject", 
			$targetTeam["team_name"]);
		
		// debit amount
		BankAccountDataService::debitAmount($websoccer, $db, $offer["sender_club_id"], $offer["offer_amount"], "directtransfer_subject", 
			$currentTeam["team_name"]);
		
		// move exchange players
		if ($offer["offer_player1"]) {
			self::_transferPlayer($websoccer, $db, $offer["offer_player1"], $offer["receiver_club_id"], $currentTeam["user_id"],
					$targetTeam["user_id"], $offer["sender_club_id"], 0, $offer["player_id"]);
		}
		if ($offer["offer_player2"]) {
			self::_transferPlayer($websoccer, $db, $offer["offer_player2"], $offer["receiver_club_id"], $currentTeam["user_id"],
					$targetTeam["user_id"], $offer["sender_club_id"], 0, $offer["player_id"]);
		}
		
		// delete offer and other offers for this player
		$db->queryDelete($websoccer->getConfig("db_prefix") . "_transfer_offer", "player_id = %d", $offer["player_id"]);
		
		// get player name for notification
		$player = PlayersDataService::getPlayerById($websoccer, $db, $offer["player_id"]);
		if ($player["player_pseudonym"]) {
			$playerName = $player["player_pseudonym"];
		} else {
			$playerName = $player["player_firstname"] . " " . $player["player_lastname"];
		}
		
		// notify and award users
		NotificationsDataService::createNotification($websoccer, $db, $currentTeam["user_id"], "transferoffer_notification_executed",
			array("playername" => $playerName), NOTIFICATION_TYPE, "player", "id=" . $offer["player_id"], $currentTeam["team_id"]);
		NotificationsDataService::createNotification($websoccer, $db, $offer["sender_user_id"], "transferoffer_notification_executed",
			array("playername" => $playerName), NOTIFICATION_TYPE, "player", "id=" . $offer["player_id"], $targetTeam['team_id']);
		
		TransfermarketDataService::awardUserForTrades($websoccer, $db, $currentTeam["user_id"]);
		TransfermarketDataService::awardUserForTrades($websoccer, $db, $offer["sender_user_id"]);
	}
	
	private static function _transferPlayer(WebSoccer $websoccer, DbConnection $db, $playerId, 
			$targetClubId, $targetUserId, $currentUserId, $currentClubId, $amount, 
			$exchangePlayer1 = 0, $exchangePlayer2 = 0) {
		$db->queryUpdate(array("verein_id" => $targetClubId, 
				"vertrag_spiele" => $websoccer->getConfig("transferoffers_contract_matches")), 
				$websoccer->getConfig("db_prefix") . "_spieler", "id = %d", $playerId);
		
		// create log
		$db->queryInsert(array(
				"bid_id" => 0,
				"datum" => $websoccer->getNowAsTimestamp(),
				"spieler_id" => $playerId,
				"seller_user_id" => $currentUserId,
				"seller_club_id" => $currentClubId,
				"buyer_user_id" => $targetUserId,
				"buyer_club_id" => $targetClubId,
				"directtransfer_amount" => $amount,
				"directtransfer_player1" => $exchangePlayer1,
				"directtransfer_player2" => $exchangePlayer2
				), $websoccer->getConfig("db_prefix") . "_transfer");
	}
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection.
	 * @param int $clubId ID of club which received offers
	 * @return int number of received offers.
	 */
	public static function countReceivedOffers(WebSoccer $websoccer, DbConnection $db, $clubId) {
	
		$columns = "COUNT(*) AS hits";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_transfer_offer";
	
		$whereCondition = "receiver_club_id = %d AND (rejected_date = 0 OR admin_approval_pending = '1')";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $clubId);
		$players = $result->fetch_array();
		$result->free();
	
		if (isset($players["hits"])) {
			return $players["hits"];
		}
	
		return 0;
	}
	
	/**
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $startIndex start index (pagination)
	 * @param int $entries_per_page number of rows per page (pagination)
	 * @param int $clubId ID of club
	 * @return array received offers
	 */
	public static function getReceivedOffers(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page, $clubId) {
		$whereCondition = "O.receiver_club_id = %d AND (O.rejected_date = 0 OR O.admin_approval_pending = '1')";
		$parameters = array($clubId);
		
		return self::_queryOffers($websoccer, $db, $startIndex, $entries_per_page, $whereCondition, $parameters);
	}
	
	/**
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection.
	 * @param int $clubId ID of club which sent offers
	 * @param int $userId ID of user which sent offers
	 * @return int nmuber of sent offers.
	 */
	public static function countSentOffers(WebSoccer $websoccer, DbConnection $db, $clubId, $userId) {
	
		$columns = "COUNT(*) AS hits";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_transfer_offer";
	
		$whereCondition = "sender_club_id = %d AND sender_user_id = %d";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, array($clubId, $userId));
		$players = $result->fetch_array();
		$result->free();
	
		if (isset($players["hits"])) {
			return $players["hits"];
		}
	
		return 0;
	}
	
	/**
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $startIndex start index (pagination)
	 * @param int $entries_per_page number of rows per page (pagination)
	 * @param int $clubId ID of club which sent offers
	 * @param int $userId ID of user which sent offers
	 * @return array sent offers
	 */
	public static function getSentOffers(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page, $clubId, $userId) {
		$whereCondition = "O.sender_club_id = %d AND O.sender_user_id = %d";
		$parameters = array($clubId, $userId);
	
		return self::_queryOffers($websoccer, $db, $startIndex, $entries_per_page, $whereCondition, $parameters);
	}
	
	private static function _queryOffers(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page, $whereCondition, $parameters) {
		$columns = array(
				"O.id" => "offer_id",
				"O.submitted_date" => "offer_submitted_date",
				"O.offer_amount" => "offer_amount",
				"O.offer_message" => "offer_message",
				"O.rejected_date" => "offer_rejected_date",
				"O.rejected_message" => "offer_rejected_message",
				"O.rejected_allow_alternative" => "offer_rejected_allow_alternative",
				"O.admin_approval_pending" => "offer_admin_approval_pending",
				"P.id" => "player_id",
				"P.vorname" => "player_firstname",
				"P.nachname" => "player_lastname",
				"P.kunstname" => "player_pseudonym",
				"P.vertrag_gehalt" => "player_salary",
				"P.marktwert" => "player_marketvalue",
				"P.w_staerke" => "player_strength",
				"P.w_technik" => "player_strength_technique",
				"P.w_kondition" => "player_strength_stamina",
				"P.w_frische" => "player_strength_freshness",
				"P.w_zufriedenheit" => "player_strength_satisfaction",
				"P.position_main" => "player_position_main",
				"SU.id" => "sender_user_id",
				"SU.nick" => "sender_user_name",
				"SC.id" => "sender_club_id",
				"SC.name" => "sender_club_name",
				"RU.id" => "receiver_user_id",
				"RU.nick" => "receiver_user_name",
				"RC.id" => "receiver_club_id",
				"RC.name" => "receiver_club_name",
				"EP1.id" => "explayer1_id",
				"EP1.vorname" => "explayer1_firstname",
				"EP1.nachname" => "explayer1_lastname",
				"EP1.kunstname" => "explayer1_pseudonym",
				"EP2.id" => "explayer2_id",
				"EP2.vorname" => "explayer2_firstname",
				"EP2.nachname" => "explayer2_lastname",
				"EP2.kunstname" => "explayer2_pseudonym"
		);
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_transfer_offer AS O";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_spieler AS P ON P.id = O.player_id";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_user AS SU ON SU.id = O.sender_user_id";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS SC ON SC.id = O.sender_club_id";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS RC ON RC.id = O.receiver_club_id";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_user AS RU ON RU.id = RC.user_id";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_spieler AS EP1 ON EP1.id = O.offer_player1";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_spieler AS EP2 ON EP2.id = O.offer_player2";
		
		$whereCondition .= " ORDER BY O.submitted_date DESC";
		
		$limit = $startIndex .",". $entries_per_page;
		
		$offers = array();
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $limit);
		while ($offer = $result->fetch_array()) {
			$offer["player_marketvalue"] = PlayersDataService::getMarketValue($websoccer, $offer);
			$offers[] = $offer;
		}
		$result->free();
		
		return $offers;
	}
	
}
?>