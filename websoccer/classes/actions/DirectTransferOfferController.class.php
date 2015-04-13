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
 * Sends an offer for a direct transfer from one manager to another.
 * 
 * @author Ingo Hofmann
 */
class DirectTransferOfferController implements IActionController {
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
		
		// check if user has team
		if ($clubId == null) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $this->_websoccer->getRequestParameter("id"));
		
		// check if player team has a manager
		if (!$player["team_user_id"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_nomanager"));
		}
		
		// check if player is already in one of user's teams
		if ($player["team_user_id"] == $this->_websoccer->getUser()->id) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_ownplayer"));
		}
			
		// check if player is unsellable or already on transfer market
		if ($player["player_unsellable"] || $player["player_transfermarket"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_unsellable"));
		}
		
		// check if there are open transfer offered by the user for manager of player
		$this->checkIfThereAreAlreadyOpenOffersFromUser($player["team_id"]);
		
		// check if user is allowed to send an alternative offer after previous offer had been rejected
		$this->checkIfUserIsAllowedToSendAlternativeOffers($player["player_id"]);
		
		// check player is allowed to transfer (Wechselsperre)
		$this->checkPlayersTransferStop($player["player_id"]);
		
		// check exchange player
		if ($parameters["exchangeplayer1"]) {
			$this->checkExchangePlayer($parameters["exchangeplayer1"]);
		}
		
		if ($parameters["exchangeplayer2"]) {
			$this->checkExchangePlayer($parameters["exchangeplayer2"]);
		}
		
		// check if team is above minimum number of players.
		if ($parameters["exchangeplayer1"] || $parameters["exchangeplayer2"]) {
			$teamSize = TeamsDataService::getTeamSize($this->_websoccer, $this->_db, $clubId);
			
			$numberOfSizeReduction = ($parameters["exchangeplayer2"]) ? 1 : 0;
			if ($teamSize < ($this->_websoccer->getConfig("transfermarket_min_teamsize") - $numberOfSizeReduction)) {
				throw new Exception($this->_i18n->getMessage("sell_player_teamsize_too_small", $teamSize));
			}
		}
		
		// check maximum number of transactions between same user within last 30 days
		$noOfTransactions = TransfermarketDataService::getTransactionsBetweenUsers($this->_websoccer, $this->_db, 
				$player["team_user_id"], $this->_websoccer->getUser()->id);
		$maxTransactions = $this->_websoccer->getConfig("transfermarket_max_transactions_between_users");
		if ($noOfTransactions >= $maxTransactions) {
			throw new Exception($this->_i18n->getMessage("transfer_bid_too_many_transactions_with_user", $noOfTransactions));
		}
		
		// check if budget is enough to pay this amount and sum of other open offers
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		$totalOffers = $this->getSumOfAllOpenOffers() + $parameters["amount"];
		if ($team["team_budget"] < $totalOffers) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_totaloffers_too_high"));
		}
		
		// check if club can pay this salary
		TeamsDataService::validateWhetherTeamHasEnoughBudgetForSalaryBid($this->_websoccer, $this->_db, $this->_i18n, $clubId, $player["player_contract_salary"]);
		
		// submit offer
		DirectTransfersDataService::createTransferOffer($this->_websoccer, $this->_db, 
			$player["player_id"], $this->_websoccer->getUser()->id, $clubId, $player["team_user_id"], $player["team_id"], 
				$parameters["amount"], $parameters["comment"], $parameters["exchangeplayer1"], $parameters["exchangeplayer2"]);
		
		// show success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("transferoffer_submitted_title"),
				$this->_i18n->getMessage("transferoffer_submitted_message")));
		
		return null;
	}
	
	private function checkIfThereAreAlreadyOpenOffersFromUser($teamId) {
		
		// do not check if admins approve transfers manually anyway
		if ($this->_websoccer->getConfig("transferoffers_adminapproval_required")) {
			return;
		}
		
		$result = $this->_db->querySelect("COUNT(*) AS hits", 
				$this->_websoccer->getConfig("db_prefix") . "_transfer_offer", 
				"rejected_date = 0 AND sender_user_id = %d AND receiver_club_id = %d",
				array($this->_websoccer->getUser()->id, $teamId));
		$count = $result->fetch_array();
		$result->free();
		
		if ($count["hits"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_open_offers_exist"));
		}
	}
	
	private function checkIfUserIsAllowedToSendAlternativeOffers($playerId) {
		$result = $this->_db->querySelect("COUNT(*) AS hits",
				$this->_websoccer->getConfig("db_prefix") . "_transfer_offer",
				"rejected_date > 0 AND rejected_allow_alternative = '0' AND player_id = %d AND sender_user_id = %d",
				array($playerId, $this->_websoccer->getUser()->id));
		$count = $result->fetch_array();
		$result->free();
	
		if ($count["hits"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_noalternative_allowed"));
		}
	}
	
	private function checkPlayersTransferStop($playerId) {
		
		// transfer stop configured?
		if ($this->_websoccer->getConfig("transferoffers_transfer_stop_days") < 1) {
			return;
		}
		
		$transferBoundary = $this->_websoccer->getNowAsTimestamp() - 24 * 3600 * $this->_websoccer->getConfig("transferoffers_transfer_stop_days");
		
		$result = $this->_db->querySelect("COUNT(*) AS hits",
				$this->_websoccer->getConfig("db_prefix") . "_transfer",
				"spieler_id = %d AND datum > %d",
				array($playerId, $transferBoundary));
		$count = $result->fetch_array();
		$result->free();
	
		if ($count["hits"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_transferstop", $this->_websoccer->getConfig("transferoffers_transfer_stop_days")));
		}
	}
	
	private function checkExchangePlayer($playerId) {
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $playerId);
		$playerName = (strlen($player["player_pseudonym"])) ? $player["player_pseudonym"] : $player["player_firstname"] . " " . $player["player_lastname"];
		
		// Check if selected players are not on transfer market and belong to own team
		if ($player["player_transfermarket"] || $player["team_user_id"] != $this->_websoccer->getUser()->id) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_exchangeplayer_on_transfermarket", $playerName));
		}
		
		// Players must not be included in any other open transfer offer
		$result = $this->_db->querySelect("COUNT(*) AS hits",
				$this->_websoccer->getConfig("db_prefix") . "_transfer_offer",
				"rejected_date = 0 AND (offer_player1 = %d OR offer_player2 = %d)",
				array($playerId, $playerId, $playerId));
		$count = $result->fetch_array();
		$result->free();
		
		if ($count["hits"]) {
			throw new Exception($this->_i18n->getMessage("transferoffer_err_exchangeplayer_involved_in_other_offers", $playerName));
		}
		
		// check transfer stop of player
		try {
			$this->checkPlayersTransferStop($playerId);
		} catch(Exception $e) {
			// replace error message
			throw new Exception($this->_i18n->getMessage("transferoffer_err_exchangeplayer_transferstop", $playerName));
		}
	}
	
	private function getSumOfAllOpenOffers() {
		$result = $this->_db->querySelect("SUM(offer_amount) AS amount",
				$this->_websoccer->getConfig("db_prefix") . "_transfer_offer",
				"rejected_date = 0 AND sender_user_id = %d",
				$this->_websoccer->getUser()->id);
		$sum = $result->fetch_array();
		$result->free();
	
		if ($sum["amount"]) {
			return $sum["amount"];
		}
		
		return 0;
	}
	
}

?>