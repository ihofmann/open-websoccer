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
 * Data service for user's premium account.
 */
class PremiumDataService {
	
	/**
	 * Provides number of statements linked to specified user.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @return int number of statements which belong to the specified team.
	 */
	public static function countAccountStatementsOfUser(WebSoccer $websoccer, DbConnection $db, $userId) {
		$columns = 'COUNT(*) AS hits';
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_premiumstatement';
		
		$whereCondition = 'user_id = %d';
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $userId);
		$statements = $result->fetch_array();
		$result->free();
		
		if (isset($statements['hits'])) {
			return $statements['hits'];
		}
		
		return 0;
	}
	
	/**
	 * Provides account statements of user.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @param int $startIndex fetch start index.
	 * @param int $entries_per_page number of items to fetch.
	 * @return array list of account statements.
	 */
	public static function getAccountStatementsOfUser(WebSoccer $websoccer, DbConnection $db, $userId, $startIndex, $entries_per_page) {
		
		$limit = $startIndex .','. $entries_per_page;
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_premiumstatement';
		
		$whereCondition = 'user_id = %d ORDER BY created_date DESC';
		
		$result = $db->querySelect('*', $fromTable, $whereCondition, $userId, $limit);
		
		$statements = array();
		while ($statement = $result->fetch_array()) {
			$statements[] = $statement;
		}
		$result->free();
		
		return $statements;
	}
	
	/**
	 * Credits specified amount to users's premium account (GIVING credit).
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @param int $amount Amount of premium money to credit. If 0, no statement will be created.
	 * @param string $subject action ID which triggered the statement.
	 * @param array $data (otpional) Array of subject data.
	 * @throws Exception if amount is negative or user could not be found.
	 */
	public static function creditAmount(WebSoccer $websoccer, DbConnection $db, $userId, $amount, $subject, $data = null) {
		if ($amount == 0) {
			return;
		}
		
		$user = UsersDataService::getUserById($websoccer, $db, $userId);
		if (!isset($user['premium_balance'])) {
			throw new Exception('user not found: ' . $userId);
		}
		
		if ($amount < 0) {
			throw new Exception('amount illegal: ' . $amount);
		} else {
			self::createTransaction($websoccer, $db, $user, $userId, $amount, $subject, $data);
		}
		
	}
	
	/**
	 * Debits specified amount from user's premium account (TAKING money).
	 * Throws Exception if balance is not enough
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @param int $amount Positive amount to debit. If 0, no statement will be created.
	 * @param string $subject action ID which triggered the statement.
	 * @param array $data (otpional) Array of subject data.
	 * @throws Exception if amount is negative, if premium credit is not enough or team could not be found.
	 */
	public static function debitAmount(WebSoccer $websoccer, DbConnection $db, $userId, $amount, $subject, $data = null) {
		if ($amount == 0) {
			return;
		}
		
		$user = UsersDataService::getUserById($websoccer, $db, $userId);
		if (!isset($user['premium_balance'])) {
			throw new Exception('user not found: ' . $userId);
		}
	
		if ($amount < 0) {
			throw new Exception('amount illegal: ' . $amount);
		}
		
		// is balance enough?
		if ($user['premium_balance'] < $amount) {
			$i18n = I18n::getInstance($websoccer->getConfig('supported_languages'));
			throw new Exception($i18n->getMessage('premium_balance_notenough'));
		}
		
		$amount = 0 - $amount;
	
		self::createTransaction($websoccer, $db, $user, $userId, $amount, $subject, $data);
	}
	
	private static function createTransaction(WebSoccer $websoccer, DbConnection $db, $user, $userId, $amount, $subject, $data) {
		
		// create transaction
		$fromTable = $websoccer->getConfig('db_prefix') .'_premiumstatement';
		$columns = array(
				'user_id' => $userId,
				'action_id' => $subject,
				'amount' => $amount,
				'created_date' => $websoccer->getNowAsTimestamp(),
				'subject_data' => json_encode($data)
				);
		$db->queryInsert($columns, $fromTable);
		
		// update user budget
		$newBudget = $user['premium_balance'] + $amount;
		$updateColumns = array('premium_balance' => $newBudget);
		$fromTable = $websoccer->getConfig('db_prefix') .'_user';
		$whereCondition = 'id = %d';
		$parameters = $userId;
		$db->queryUpdate($updateColumns, $fromTable, $whereCondition, $parameters);
		
		// also update user profile, if executed by user.
		if ($userId == $websoccer->getUser()->id) {
			$websoccer->getUser()->premiumBalance = $newBudget;
		}
	}
	
	/**
	 * Creates a payment log entry and credits premium balance according to available price options.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection.
	 * @param int $userId ID of user to credit for.
	 * @param number $amount Real money amount that has been registered. Will be stored with multiplied by 100, in order to store float numbers as integers.
	 * @param string $subject Subject id for premium statement. Usually ID of action which triggered the payment.
	 * @throws Exception if either the amount is <= 0 or if no price option is available for the specified amount.
	 */
	public static function createPaymentAndCreditPremium(WebSoccer $websoccer, DbConnection $db, $userId, $amount, $subject) {
		
		if ($amount <= 0) {
			throw new Exception('Illegal amount: ' . $amount);
		}
		
		$realAmount = $amount * 100;
		
		// create payment statement
		$db->queryInsert(array(
				'user_id' => $userId,
				'amount' => $realAmount,
				'created_date' => $websoccer->getNowAsTimestamp()
				), $websoccer->getConfig('db_prefix') . '_premiumpayment');
		
		// get premium amount to credit
		$priceOptions = explode(',', $websoccer->getConfig('premium_price_options'));
		if (count($priceOptions)) {
			foreach ($priceOptions as $priceOption) {
				$optionParts = explode(':', $priceOption);
				
				$realMoney = trim($optionParts[0]);
				$realMoneyAmount = $realMoney * 100;
				$premiumMoney = trim($optionParts[1]);
				
				// credit amount and end here
				if ($realAmount == $realMoneyAmount) {
					self::creditAmount($websoccer, $db, $userId, $premiumMoney, $subject);
					return;
				}
			}
		}
		
		// if reached here, no price option has been found for this amount
		throw new Exception('No price option found for amount: ' . $amount);
	}
	
	/**
	 * Provide latest payment log entries of specified user.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @param int $limit number of entries to fetch.
	 * @return array List of payment statements.
	 */
	public static function getPaymentsOfUser(WebSoccer $websoccer, DbConnection $db, $userId, $limit) {
	
		$fromTable = $websoccer->getConfig('db_prefix') . '_premiumpayment';
		$whereCondition = 'user_id = %d ORDER BY created_date DESC';
	
		$result = $db->querySelect('*', $fromTable, $whereCondition, $userId, $limit);
	
		$statements = array();
		while ($statement = $result->fetch_array()) {
			$statement['amount'] = $statement['amount'] / 100;
			$statements[] = $statement;
		}
		$result->free();
	
		return $statements;
	}
}
?>