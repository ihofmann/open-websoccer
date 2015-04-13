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
 * Submits a new bid on the transfer market.
 */
class TransferBidController implements IActionController {
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
		if (!$this->_websoccer->getConfig('transfermarket_enabled')) {
			return;
		}
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		$playerId = $parameters['id'];
		
		// check if user has a club
		if ($clubId < 1) {
			throw new Exception($this->_i18n->getMessage('error_action_required_team'));
		}
		
		// check if it is not own player
		$player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $playerId);
		if ($user->id == $player['team_user_id']) {
			throw new Exception($this->_i18n->getMessage('transfer_bid_on_own_player'));
		}
		
		// check if player is still on transfer list
		if (!$player['player_transfermarket']) {
			throw new Exception($this->_i18n->getMessage('transfer_bid_player_not_on_list'));
		}
		
		// check that auction is not over
		$now = $this->_websoccer->getNowAsTimestamp();
		if ($now > $player['transfer_end']) {
			throw new Exception($this->_i18n->getMessage('transfer_bid_auction_ended'));
		}
		
		// player must accept the new salary
		$minSalary = $player['player_contract_salary'] * 1.1;
		if ($parameters['contract_salary'] < $minSalary) {
			throw new Exception($this->_i18n->getMessage('transfer_bid_salary_too_less'));
		}
		
		// check goal bonus
		$minGoalBonus = $player['player_contract_goalbonus'] * 1.1;
		if ($parameters['contract_goal_bonus'] < $minGoalBonus) {
			throw new Exception($this->_i18n->getMessage('transfer_bid_goalbonus_too_less'));
		}
		
		// check if user has been already traded too often with the other user
		if ($player['team_id'] > 0) {
			$noOfTransactions = TransfermarketDataService::getTransactionsBetweenUsers($this->_websoccer, $this->_db, $player['team_user_id'], $user->id);
			$maxTransactions = $this->_websoccer->getConfig('transfermarket_max_transactions_between_users');
			if ($noOfTransactions >= $maxTransactions) {
				throw new Exception($this->_i18n->getMessage('transfer_bid_too_many_transactions_with_user', $noOfTransactions));
			}
		}
		
		// get existing highest bid
		$highestBid = TransfermarketDataService::getHighestBidForPlayer($this->_websoccer, $this->_db, $parameters['id'], $player['transfer_start'], $player['transfer_end']);
		
		// with transfer-fee: check if own bid amount is higher than existing bid
		if ($player['team_id'] > 0) {
			$minBid = $player['transfer_min_bid'] - 1;
			if (isset($highestBid['amount'])) {
				$minBid = $highestBid['amount'];
			}
			
			if ($parameters['amount'] <= $minBid) {
				throw new Exception($this->_i18n->getMessage('transfer_bid_amount_must_be_higher', $minBid));
			}
			// without transfer fee: compare contract conditions
		} else if (isset($highestBid['contract_matches'])) {
			
			// we compare the total income of the whole offered contract duraction
			$ownBidValue = $parameters['handmoney'] + $parameters['contract_matches'] * $parameters['contract_salary'];
			
			$opponentSalary = $highestBid['hand_money'] + $highestBid['contract_matches'] * $highestBid['contract_salary'];
		
			// consider goal bonus only for midfield and striker, assuming player scores 10 goals
			if ($player['player_position'] == 'midfield' || $player['player_position'] == 'striker') {
				$ownBidValue += 10 * $parameters['contract_goal_bonus'];
				$opponentSalary += 10 * $highestBid['contract_goalbonus'];
			}
			
			if ($ownBidValue <= $opponentSalary) {
				throw new Exception($this->_i18n->getMessage('transfer_bid_contract_conditions_too_low'));
			}
		
		}
		
		// check if budget is enough (hand money/fee + assume that the team consists of 20 players with same salary, then it should survive for 2 matches)
		TeamsDataService::validateWhetherTeamHasEnoughBudgetForSalaryBid($this->_websoccer, $this->_db, $this->_i18n, $clubId, $parameters['contract_salary']);
		
		// check if budget is enough for all current highest bids of user.
		$team = TeamsDataService::getTeamSummaryById($this->_websoccer, $this->_db, $clubId);
		$result = $this->_db->querySelect('SUM(abloese) + SUM(handgeld) AS bidsamount', $this->_websoccer->getConfig('db_prefix') .'_transfer_angebot', 
				'user_id = %d AND ishighest = \'1\'', $user->id);
		$bids = $result->fetch_array();
		$result->free();
		if (isset($bids['bidsamount']) && ($parameters['handmoney'] + $parameters['amount'] + $bids['bidsamount']) >= $team['team_budget']) {
			throw new Exception($this->_i18n->getMessage('transfer_bid_budget_for_all_bids_too_less'));
		}
		
		// save bid
		$this->saveBid($playerId, $user->id, $clubId, $parameters);
		
		// mark previous highest bid as outbidden
		if (isset($highestBid['bid_id'])) {
			$this->_db->queryUpdate(array('ishighest' => '0'), $this->_websoccer->getConfig('db_prefix') .'_transfer_angebot', 
					'id = %d', $highestBid['bid_id']);
		}
		
		// notify outbidden user
		if (isset($highestBid['user_id']) && $highestBid['user_id']) {
			$playerName = (strlen($player['player_pseudonym'])) ? $player['player_pseudonym'] : $player['player_firstname'] . ' ' . $player['player_lastname'];
			NotificationsDataService::createNotification($this->_websoccer, $this->_db, $highestBid['user_id'], 
				'transfer_bid_notification_outbidden', array('player' => $playerName), 'transfermarket', 'transfer-bid', 'id=' . $playerId);
		}
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage('transfer_bid_success'),
				''));
		
		return null;
	}
	
	private function saveBid($playerId, $userId, $clubId, $parameters) {
		
		$columns['spieler_id'] = $playerId;
		$columns['user_id'] = $userId;
		$columns['datum'] = $this->_websoccer->getNowAsTimestamp();
		$columns['abloese'] = $parameters['amount'];
		$columns['handgeld'] = $parameters['handmoney'];
		$columns['vertrag_spiele'] = $parameters['contract_matches'];
		$columns['vertrag_gehalt'] = $parameters['contract_salary'];
		$columns['vertrag_torpraemie'] = $parameters['contract_goal_bonus'];
		$columns['verein_id'] = $clubId;
		$columns['ishighest'] = '1';
		
		$fromTable = $this->_websoccer->getConfig('db_prefix') .'_transfer_angebot';
		
		$this->_db->queryInsert($columns, $fromTable);
		
	}
	
}

?>