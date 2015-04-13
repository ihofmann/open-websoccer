<?php
require_once(dirname(__FILE__).'/sofortLibAbstract.inc.php');

/**
 * This class is  for retrieving information about transactions,
 * you can search by transaction-id or by date
 *
 * eg: $SofortLibTransactionData = new SofortLibTransactionData('yourapikey');
 *
 * $SofortLibTransactionData->setTransaction('1234-456-789654-31321')->sendRequest();
 *
 * echo $SofortLibTransactionData->getStatus();
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-10-23 14:29:19 +0200 (Wed, 23 Oct 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibTransactionData.inc.php 279 2013-10-23 12:29:19Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class SofortLibTransactionData extends SofortLibAbstract {
	
	protected $_rootTag = 'transaction_request';
	
	private $_count = 0;
	
	
	/**
	 * Use this function if you want to request
	 * detailed information about several transactions
	 * at once
	 *
	 * @param string $transaction
	 * @return SofortLibTransactionData $this
	 */
	public function addTransaction($transaction) {
		if(!isset($this->_parameters['transaction']) || !is_array($this->_parameters['transaction'])) {
			$this->_parameters['transaction'] = array();
		}
		
		if (is_array($transaction)) {
			foreach($transaction as $element) {
				$this->_parameters['transaction'][] = $element;
			}
		} else {
			$this->_parameters['transaction'][] = $transaction;
		}
		
		return $this;
	}
	
	
	/**
	 * You can request all transactions of a certain time
	 * period
	 *
	 * use setNumber() to limit the results
	 *
	 * @param string $from date possible formats: 2011-01-25 or 2011-01-25T19:01:02+02:00
	 * @param string $to date possible formats: 2011-01-25 or 2011-01-25T19:01:02+02:00
	 * @return SofortLibTransactionData $this
	 * @see setNumber()
	 */
	public function setTime($from, $to) {
		$this->_parameters['from_time'] = $from;
		$this->_parameters['to_time'] = $to;
		return $this;
	}
	
	
	/**
	 * You can limit the number of results
	 *
	 * @param int $number number of results [0-100]
	 * @param int $page result page (default 1)
	 * @return SofortLibTransactionData $this
	 * @see setTime()
	 */
	public function setNumber($number, $page = 1) {
		$this->_parameters['number'] = $number;
		$this->_parameters['page'] = $page;
		return $this;
	}
	
	
	/**
	 * Request for transactions with their status changed within a vertain time
	 *
	 * @param string $from possible formats: 2011-01-25 or 2011-01-25T19:01:02+02:00
	 * @param string $to possible formats: 2011-01-25 or 2011-01-25T19:01:02+02:00
	 * @return SofortTransactionData
	 */
	public function setStatusModifiedTime($from, $to) {
		$this->_parameters['from_status_modified_time'] = $from;
		$this->_parameters['to_status_modified_time'] = $to;
		return $this;
	}
	
	
	/**
	 * Request for transactions with certain status
	 *
	 * @param string $status (loss|pending|received|refunded|untraceable)
	 * @return SofortTransactionData
	 */
	public function setStatus($status) {
		$this->_parameters['status'] = $status;
		return $this;
	}
	
	
	
	/**
	 * Request for transactions with certain status reason
	 *
	 * @param string $statusReason (not_credited_yet|not_credited|refunded|compensation|
	 * 								credited|canceled|confirm_invoice|confirmation_period_expired|
	 * 								wait_for_money|partially_credited|overpayment|rejected|sofort_bank_account_needed|
									prefinanced|acquired|late_succeed)
	 * @return SofortTransactionData
	 */
	public function setStatusReason($statusReason) {
		$this->_parameters['status_reason'] = $statusReason;
		return $this;
	}
	
	
	/**
	 * Getter for count, the number of transaction returned by the response
	 * @return int
	 */
	public function getCount() {
		return $this->_count;
	}
	
	
	/**
	 * Setter for transaction-counter
	 * @param int $count
	 * @return SofortTransactionData $this
	 */
	public function setCount($count) {
		$this->_count = $count;
		return $this;
	}
	
	
	/**
	 * Checks wether given index ($i) is within the returned boundaries and if the key and its data exists.
	 *
	 * @param integer $i
	 * @param string $tag
	 * @param string $parentTag optional
	 * @param integer $n optional
	 * @return boolean
	 */
	private function _extractValue($i, $tag, $parentTag = '', $n = false) {
		//Check wether the given index is in the responses range
		if ($i < 0 || $i >= $this->_count) {
			return false;
		}
		
		if($parentTag == '') {
			return $this->_extractValueSimpleTag($i, $tag);
		} else if($parentTag == 'status_history_items') {
			return $this->_extractValueStatusHistoryItem ($i, $tag, $parentTag, $n);
		} else if($n !== false && isset($this->_response[$i][$parentTag][$tag][$n])) {
			//Special cases: user_variable and reason both can have $n elements
			return $this->_extractValueGroupedDataNumbered($i, $tag, $parentTag, $n);
		} else {
			//Some Data is nested (holder and sender data)
			return $this->_extractValueGroupedData($i, $tag, $parentTag);
		}
	}
	
	/**
	 * Returns the Data of the given tag
	 *
	 * @param integer $i
	 * @param string $tag
	 * @return boolean
	 */
	private function _extractValueSimpleTag($i, $tag) {
		return isset($this->_response[$i][$tag]['@data']) ? $this->_response[$i][$tag]['@data'] : false;
	}
	
	
	/**
	 * Returns the nth Array of the Status History item
	 *
	 * @param integer $i
	 * @param string $tag
	 * @param string $parentTag
	 * @param integer $n
	 * @return multitype:NULL |boolean
	 */
	private function _extractValueStatusHistoryItem ($i, $tag, $parentTag, $n) {
		if(isset($this->_response[$i][$parentTag][$tag][$n])) {
			return array($this->_response[$i][$parentTag][$tag][$n]['status']['@data'],
							$this->_response[$i][$parentTag][$tag][$n]['status_reason']['@data'],
							$this->_response[$i][$parentTag][$tag][$n]['time']['@data']);
		} else {
			return false;
		}
	}
	
	
	/**
	 * Checks for Elements that can have n Elements, Returns the "nth" Element
	 *
	 * @param integer $i
	 * @param string $tag
	 * @param string $parentTag
	 * @param integer $n
	 * @return boolean
	 */
	private function _extractValueGroupedDataNumbered($i, $tag, $parentTag, $n) {
		return isset($this->_response[$i][$parentTag][$tag][$n]['@data']) ? $this->_response[$i][$parentTag][$tag][$n]['@data'] : false;
	}
	
	
	/**
	 * Checks for Grouped Data within the repsonse array, Returns Data on Success, else false.
	 *
	 * @param integer $i
	 * @param string $tag
	 * @param string $parentTag
	 * @return boolean
	 */
	private function _extractValueGroupedData($i, $tag, $parentTag) {
		return isset($this->_response[$i][$parentTag][$tag]['@data']) ? $this->_response[$i][$parentTag][$tag]['@data'] : false;
	}
	
	
	/**
	 * Returns the payment method of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string su|paycode|billcode
	 */
	public function getPaymentMethod($i = 0) {
		return $this->_extractValue($i, 'payment_method');
	}
	
	
	/**
	 * Returns the state of consumer_protection if set
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return boolean
	 */
	public function getConsumerProtection($i = 0) {
		$paymentMethod = $this->getPaymentMethod($i);
		
		if (in_array($paymentMethod, array('su'))) {
			return $this->_extractValue($i, 'consumer_protection', $paymentMethod);
		} else {
			return false;
		}
	}
	
	
	/**
	 * Returns the status of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string status of transaction
	 */
	public function getStatus($i = 0) {
		return $this->_extractValue($i, 'status');
	}
	
	
	/**
	 * Returns the detailed status description of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string message
	 */
	public function getStatusReason($i = 0) {
		return $this->_extractValue($i, 'status_reason');
	}
	
	
	/**
	 * Returns an array with an status_history_item (status (code), status_reason and time)
	 *
	 * @param integer $i if you request multiple transactions at once you can set the number here
	 * @param integer $n if there are multiple status_history_items set the number
	 * @return Ambigous <boolean, multitype:NULL >
	 */
	public function getStatusHistoryItem($i = 0, $n= 0) {
		return $this->_extractValue($i, 'status_history_item', 'status_history_items', $n);
	}
	
	
	/**
	 * Returns the time of the last status-change so you can check if sth. changed
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string time e.g. 2011-01-01T12:35:09+01:00 use strtotime() to convert it to unixtime
	 */
	public function getStatusModifiedTime($i = 0) {
		return $this->_extractValue($i, 'status_modified');
	}
	
	
	/**
	 * Returns the language code of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string language code
	 */
	public function getLanguageCode($i = 0) {
		return $this->_extractValue($i, 'language_code');
	}
	
	
	/**
	 * Returns the total amount of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return double amount
	 */
	public function getAmount($i = 0) {
		return $this->_extractValue($i, 'amount');
	}
	
	
	/**
	 * Refund, if a transaction was refundend. amount = amountRefunded if everything was refunded
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return double amount
	 */
	public function getAmountRefunded($i = 0) {
		return $this->_extractValue($i, 'amount_refunded');
	}
	
	
	/**
	 * Returns the currency of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string EUR|USD|GBP....
	 */
	public function getCurrency($i = 0) {
		return $this->_extractValue($i, 'currency_code');
	}
	
	
	/**
	 * Returns the transaction customer emailadress
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string emailadress
	 */
	public function getEmailCustomer($i = 0) {
		return $this->_extractValue($i, 'email_customer');
	}
	
	
	/**
	 * Returns the transaction customer phonenumber
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string emailadress
	 */
	public function getPhoneNumberCustomer($i = 0) {
		return $this->_extractValue($i, 'phone_customer');
	}
	
	
	/**
	 * Returns the transactions exchange rate
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string emailadress
	 */
	public function getExchangeRate($i = 0) {
		return $this->_extractValue($i, 'exchange_rate');
	}
	
	
	/**
	 * Returns the transactions exchange rate
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string emailadress
	 */
	public function getPaycode($i = 0) {
		return $this->_extractValue($i, 'code', 'paycode');
	}
	
	
	/**
	 * Returns the transactions Fees (Part of Costs)
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return double fees
	 */
	public function getCostsFees ($i = 0) {
		return $this->_extractValue($i, 'fees', 'costs');
	}
	
	
	/**
	 * Returns the transactions costs Currency
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string EUR|USD|GBP.... >
	 */
	public function getCostsCurrencyCode ($i = 0) {
		return $this->_extractValue($i, 'currency_code', 'costs');
	}
	
	
	/**
	 * Returns the transactions exchange rate
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return double exchange_rate
	 */
	public function getCostsExchangeRate ($i = 0) {
		return $this->_extractValue($i, 'exchange_rate', 'costs');
	}
	
	
	/**
	 * Returns the transactions exchange rate
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string emailadress
	 */
	public function getBillcode($i = 0) {
		return $this->_extractValue($i, 'code', 'billcode');
	}
	
	
	/**
	 * Returns the transaction id of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string transaction id
	 */
	public function getTransaction($i = 0) {
		return $this->_extractValue($i, 'transaction');
	}
	
	
	/**
	 * Returns an array containing reason of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @param int $n get the reason line (0 = line 1, 1 = line 2)
	 * @return string transaction reasons
	 */
	public function getReason($i = 0, $n = 0) {
		return $this->_extractValue($i, 'reason', 'reasons', $n);
	}
	
	
	/**
	 * Returns the user variable of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @param int $n number of the variable
	 * @return string the content of this variable
	 */
	public function getUserVariable($i = 0, $n = 0) {
		return $this->_extractValue($i, 'user_variable', 'user_variables', $n);
	}
	
	
	/**
	 * Returns the time of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string time e.g. 2011-01-01T12:35:09+01:00 use strtotime() to convert it to unixtime
	 */
	public function getTime($i = 0) {
		return $this->_extractValue($i, 'time');
	}
	
	
	/**
	 * Returns the project id of a transaction
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return int project id
	 */
	public function getProjectId($i = 0) {
		return $this->_extractValue($i, 'project_id');
	}
	
	
	/**
	 * Returns the holder of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string recipient holder
	 */
	public function getRecipientHolder($i = 0) {
		return $this->_extractValue($i, 'holder', 'recipient');
	}
	
	
	/**
	 * Returns the account number of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string account number
	 */
	public function getRecipientAccountNumber($i = 0) {
		return $this->_extractValue($i, 'account_number', 'recipient');
	}
	
	
	/**
	 * Returns the bank code of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string recipient bank code
	 */
	public function getRecipientBankCode($i = 0) {
		return $this->_extractValue($i, 'bank_code', 'recipient');
	}
	
	
	/**
	 * Returns the country code of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string country code
	 */
	public function getRecipientCountryCode($i = 0) {
		return $this->_extractValue($i, 'country_code', 'recipient');
	}
	
	
	/**
	 * Returns the bank name of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string bank name
	 */
	public function getRecipientBankName($i = 0) {
		return $this->_extractValue($i, 'bank_name', 'recipient');
	}
	
	
	/**
	 * Returns the BIC of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string recipient BIC
	 */
	public function getRecipientBic($i = 0) {
		return $this->_extractValue($i, 'bic', 'recipient');
	}
	
	
	/**
	 * Returns the IBAN of the receiving account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string recipient IBAN
	 */
	public function getRecipientIban($i = 0) {
		return $this->_extractValue($i, 'iban', 'recipient');
	}
	
	
	/**
	 * Returns the holder of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender holder
	 */
	public function getSenderHolder($i = 0) {
		return $this->_extractValue($i, 'holder', 'sender');
	}
	
	
	/**
	 * Returns the account number of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender account number
	 */
	public function getSenderAccountNumber($i = 0) {
		return $this->_extractValue($i, 'account_number', 'sender');
	}
	
	
	/**
	 * Returns the bank code of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender bank code
	 */
	public function getSenderBankCode($i = 0) {
		return $this->_extractValue($i, 'bank_code', 'sender');
	}
	
	
	/**
	 * Returns the country code of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender country code
	 */
	public function getSenderCountryCode($i = 0) {
		return $this->_extractValue($i, 'country_code', 'sender');
	}
	
	
	/**
	 * Returns the bank name of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender bank name
	 */
	public function getSenderBankName($i = 0) {
		return $this->_extractValue($i, 'bank_name', 'sender');
	}
	
	
	/**
	 * Returns the BIC of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender BIC
	 */
	public function getSenderBic($i = 0) {
		return $this->_extractValue($i, 'bic', 'sender');
	}
	
	
	/**
	 * Returns the IBAN of the sending account
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return string sender IBAN
	 */
	public function getSenderIban($i = 0) {
		return $this->_extractValue($i, 'iban', 'sender');
	}
	
	
	/**
	 * Checks if the transaction was a test
	 * @param int $i if you request multiple transactions at once you can set the number here
	 * @return boolean
	 */
	public function isTest($i = 0) {
		return $this->_extractValue($i, 'test');
	}
	
	
	/**
	 * Parse the XML (override)
	 * @see SofortLibAbstract::_parse()
	 * @return void
	 */
	protected function _parse() {
		if (isset($this->_response['transactions']['transaction_details'])) {
			$transactionFromXml = (isset($this->_response['transactions']['transaction_details'][0]))
				? $this->_response['transactions']['transaction_details']
				: $this->_response['transactions'];
			$transactions = array();
			
			foreach ($transactionFromXml as $transaction) {
				if (!empty($transaction)) {
					//XML to Array Problem: ist unter einem Knoten nur ein Punkt, wird logischerweise kein Index eingefügt,
					//an einigen stellen erwarten wir aber auf alle Fälle einen, deshalb die dreieckstauschaktionen.
					$special_cases = array('status_history_items' => 'status_history_item', 'reasons' => 'reason', 'user_variables' => 'user_variable');
					
					foreach ($special_cases AS $key => $value) {
						if(isset($transaction[$key][$value]) && !isset($transaction[$key][$value][0])) {
							$tmp = $transaction[$key][$value];
							unset($transaction[$key][$value]);
							$transaction[$key][$value][0] = $tmp;
						}
					}
					$transactions[] = $transaction;
				}
			}
			
			$this->_response = $transactions;
			$this->_count = count($transactions);
		} else {
			$this->_count = 0;
		}
	}
}