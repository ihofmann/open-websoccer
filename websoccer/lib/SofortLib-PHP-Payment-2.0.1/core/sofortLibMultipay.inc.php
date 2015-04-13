<?php
require_once(dirname(__FILE__).'/sofortLibAbstract.inc.php');

/**
 * Abstract Multipay Class, provides attributes and methods for Invoice and SofortUeberweisung
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-07-30 14:08:33 +0200 (Tue, 30 Jul 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibMultipay.inc.php 256 2013-07-30 12:08:33Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 *
 */
abstract class SofortLibMultipay extends SofortLibAbstract {
	
	/**
	 * Root Tag for the XML to be rendered
	 * @var String
	 */
	protected $_rootTag = 'multipay';
	
	/**
	 * Container for the requests transactionId
	 * @var String
	 */
	protected $_transaction;
	
	/**
	 *
	 * @var Boolean|String
	 */
	protected $_paymentUrl;
	
	
	/**
	 * Setter for Amount
	 * @param float $amount
	 * @return SofortLibMultipay $this
	 */
	protected function _setAmount($amount = 0) {
		$this->_parameters['amount'] = $amount;
		return $this;
	}
	
	
	/**
	 * Set the version of this payment module
	 * this is helpfull so the support staff can easily
	 * find out if someone uses an outdated module
	 *
	 * @param string $version version string of your module
	 * @return SofortLibMultipay $this
	 */
	public function setVersion($version) {
		$this->_parameters['interface_version'] = $version;
		return $this;
	}
	
	
	/**
	 * Set data of account
	 *
	 * @param string $bankCode bank code of bank
	 * @param string $accountNumber account number
	 * @param string $holder Name/Holder of this account
	 * @return SofortLibMultipay $this
	 */
	public function setSenderAccount($bankCode, $accountNumber, $holder) {
		$this->_parameters['sender'] = array(
			'bank_code' => $bankCode,
			'account_number' => $accountNumber,
			'holder' => $holder,
		);
		return $this;
	}
	
	
	/**
	 * Setter for senders country code (ISO 3166-1)
	 * @param string $countryCode
	 * @return SofortLibMultipay $this
	 */
	public function setSenderCountryCode($countryCode) {
		$this->_parameters['sender']['country_code'] = $countryCode;
		return $this;
	}
	
	
	/**
	 * Setter for senders iban
	 * @param string $iban
	 * @return SofortLibMultipay $this
	 */
	public function setSenderIban($iban) {
		$this->_parameters['sender']['iban'] = $iban;
		return $this;
	}
	
	
	/**
	 * Setter for senders BIC
	 * @param unknown_type $bic
	 * @return SofortLibMultipay $this
	 */
	public function setSenderBic($bic) {
		$this->_parameters['sender']['bic'] = $bic;
		return $this;
	}
	
	
	/**
	 * Shortens the reasonstring
	 * @param string $reason
	 * @param string $pattern
	 * @param int $reasonLength
	 * @return string
	 */
	protected function _shortenReason($reason, $pattern = '#[^a-zA-Z0-9+-\.,]#', $reasonLength = 27) {
		$reason = preg_replace($pattern, ' ', $reason);
		$reason = substr($reason, 0, $reasonLength);
		return $reason;
	}
	
	
	/**
	 * After configuration and sending this request
	 * you can use this function to redirect the customer
	 * to the payment form
	 *
	 * @return string url of payment form
	 */
	public function getPaymentUrl() {
		$this->_paymentUrl = isset($this->_response['new_transaction']['payment_url']['@data'])
			? $this->_response['new_transaction']['payment_url']['@data']
			: false;
		return $this->_paymentUrl;
	}
	
	
	/**
	 * After configuration and sending this request
	 * you can use this function to get the transactions
	 * transaction ID
	 *
	 * @return string
	 */
	public function getTransactionId() {
		$this->_transaction = isset($this->_response['new_transaction']['transaction']['@data'])
			? $this->_response['new_transaction']['transaction']['@data']
			: false;
		return $this->_transaction;
	}
	
	
	/**
	 * Setter for languageCode
	 * @param string $languageCode | fallback EN
	 * @return SofortLibMultipay $this
	 */
	public function setLanguageCode($languageCode) {
		$this->_parameters['language_code'] = !empty($languageCode) ? $languageCode : 'EN';
		return $this;
	}
	
	
	/**
	 * Timeout how long this transaction configuration will be valid for
	 * this is the time between the generation of the payment url and
	 * the user completing the form, should be at least two to three minutes
	 * defaults to unlimited if not set
	 *
	 * @param int $timeout timeout in seconds
	 * @return SofortLibMultipay $this
	 */
	public function setTimeout($timeout) {
		$this->_parameters['timeout'] = $timeout;
		return $this;
	}
	
	
	/**
	 * Setter for Amount
	 * @param float $amount
	 * @return SofortLibMultipay $this
	 */
	public function setAmount($amount = 0) {
		$this->_setAmount($amount);
		return $this;
	}
	
	
	
	
	/**
	 * Set the email address of the customer
	 * this will be used for sofortvorkasse and sofortrechnung
	 *
	 * @param string $customersEmail email address
	 * @return SofortLibMultipay $this
	 */
	public function setEmailCustomer($customersEmail) {
		$this->_parameters['email_customer'] = $customersEmail;
		return $this;
	}
	
	
	/**
	 * Set the phone number of the customer
	 *
	 * @param string $customersPhone phone number
	 * @return SofortLibMultipay $this
	 */
	public function setPhoneCustomer($customersPhone) {
		$this->_parameters['phone_customer'] = $customersPhone;
		return $this;
	}
	
	
	/**
	 * Add another variable this can be your internal order id or similar
	 *
	 * @param string $userVariable the contents of the variable
	 * @return SofortLibMultipay $this
	 */
	public function setUserVariable($userVariable) {
		if (!is_array($userVariable)) {
			$userVariable = array($userVariable);
		}
		
		$this->_parameters['user_variables']['user_variable'] = $userVariable;
		return $this;
	}
	
	/**
	 * Setter for Reasons
	 * @param string $reason1
	 * @param (optional) string $reason2
	 * @return SofortLibAbstract $this
	 */
	public function setReason($reason1, $reason2 = '') {
		if (!empty($reason1)) {
			$reason1 = $this->_shortenReason($reason1);
			$reason2 = (!empty($reason2))
				? $this->_shortenReason($reason2)
				: $reason2;
			$this->_parameters['reasons']['reason'] = array($reason1, $reason2);
		}
	
		return $this;
	}
	
	
	/**
	 * Getter for reasons
	 * @return string
	 */
	public function getReason() {
		if(isset($this->_parameters['reasons']['reason'])) {
			return $this->_parameters['reasons']['reason'];
		} else {
			return false;
		}
	}
}