<?php
/**
 * @mainpage
 * Base class for SOFORT XML-Api
 * This class implements basic http authentication and an xml-parser
 * for parsing response messages
 *
 * Requires libcurl and openssl
 *
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-10-08 08:34:40 +0200 (Tue, 08 Oct 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibAbstract.inc.php 276 2013-10-08 06:34:40Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 *
 */
require_once(dirname(__FILE__).'/sofortLibFactory.php');

define('SOFORTLIB_VERSION', '2.0');

abstract class SofortLibAbstract {
	
	CONST GATEWAY_URL = "https://api.sofort.com/api/xml";
	
	protected $_apiVersion = '1.0';
	
	/**
	 *
	 * @var boolean
	 */
	protected $_validateOnly = false;
	
	/**
	 * Object for the Data Handler (usually XML-Data Handler)
	 * @var object
	 */
	protected $_DataHandler = null;
	
	/**
	 * Object for the logger
	 * @var object
	 */
	protected $_Logger = null;
	
	/**
	 * Array, that contains data and structure which will be send to the API (normally as XML)
	 * @var array
	 */
	protected $_parameters = array();
	
	/**
	 * Complete Config Key as provided in User Account on sofort.com
	 * @var string
	 */
	protected $_configKey = '';
	
	/**
	 * User ID from sofort.com
	 * @var string
	 */
	protected $_userId = '';
	
	/**
	 * Project ID from sofort.com
	 * @var string
	 */
	protected $_projectId = '';
	
	/**
	 * Api Key as provided in User Account on sofort.com
	 * @var string
	 */
	protected $_apiKey = '';
	
	/**
	 * Defines the used part of the API
	 * @var string
	 */
	protected $_rootTag = '';
	
	/**
	 * Contains the request Data, that has been sent to the API
	 * @var array
	 */
	protected $_request;
	
	/**
	 * Provides the parsed response.
	 * @var array
	 */
	protected $_response;
	
	/**
	 * Contains the allowed products
	 * @var array
	 */
	protected $_products = array('global', 'su');
	
	/**
	 * Array for the warnings that occured
	 * @var array
	 */
	public $warnings = array();
	
	/**
	 * Array for the errors that occured
	 * @var array
	 */
	public $errors = array();
	
	/**
	 * Define if logging is en/disabled
	 * @var boolean
	 */
	public $enableLogging = false;
	
	
	public function __construct($configKey, $apiUrl = '') {
		$this->setConfigKey($configKey);
		
		if ($apiUrl == '') {
			$apiUrl = (getenv('sofortApiUrl') != '') ? getenv('sofortApiUrl') : self::GATEWAY_URL;
		}
		
		$SofortLibHttp = SofortLibFactory::getHttpConnection($apiUrl);
		$XmlDataHandler = SofortLibFactory::getDataHandler($configKey);
		$this->setDataHandler($XmlDataHandler);
		$FileLogger = SofortLibFactory::getLogger();
		$this->setLogger($FileLogger);
		$this->_DataHandler->setConnection($SofortLibHttp);
		$this->enableLogging = (getenv('sofortDebug') == 'true') ? true : false;
	}
	
	
	/**
	 * checks (response)-array for error
	 * @param string $paymentMethod - 'all', 'su' (if unknown then it uses "all")
	 * @param (optional) array $message response array
	 * @return boolean true if errors found (in given payment-method or in 'global') ELSE false
	 */
	public function isError($paymentMethod = 'all', $message = '') {
		$return = false;
		
		if ($message == '') {
			$message = $this->errors;
		}
		
		if (empty($message)) {
			$return = false;
		}
		
		if (!in_array($paymentMethod, $this->_products)) {
			$paymentMethod = 'all';
		}
		
		if ($paymentMethod == 'all') {
			$return = $this->_isErrorWarning($message);
		} else {
			//paymentMethod-specific search
			if (is_array($message)) {
				return $this->_getPaymentSpecificError($paymentMethod, $message);
			}
		}
		
		return $return;
	}
	
	
	/**
	 * checks (response)- for Paymentspecific error
	 * @param string $paymentMethod - 'all', 'su' (if unknown then it uses "all")
	 * @param (optional) array $message response array
	 * @return boolean true if errors found (in given payment-method or in 'global') ELSE false
	 */
	private function _getPaymentSpecificError($paymentMethod, $message) {
		$return = false;
		$messagePaymentMethodSetNotEmpty = isset($message[$paymentMethod]) && !empty($message[$paymentMethod]);
		$messageGlobalSetNotEmpty = isset($message['global']) && !empty($message['global']);
		$messagePaymentMethodOrGlobalSetNotEmpty = $messagePaymentMethodSetNotEmpty || $messageGlobalSetNotEmpty;
		
		if ($messagePaymentMethodOrGlobalSetNotEmpty) {
			$return =  true;
		}
		return $return;
	}
	
	
	/**
	 * Getter for warnings
	 * @param string $paymentMethod - 'all', 'su' (default "all")
	 * @param (optional) array $message response array
	 * @return empty array if no warnings exists ELSE array with warning-codes and warning-messages
	 */
	public function getWarnings($paymentMethod = 'all', $message = '') {
		if ($message == '') {
			$message = $this->warnings;
		}
		
		$supportedPaymentMethods = $this->_products;
		
		if (!in_array($paymentMethod, $supportedPaymentMethods)) {
			$paymentMethod = 'all';
		}
		
		$returnArray = array();
		
		//return global + selected payment method
		foreach ($supportedPaymentMethods as $pm) {
			if (($paymentMethod == 'all' || $pm == 'global' || $paymentMethod == $pm) && array_key_exists($pm, $message)) {
				$returnArray = array_merge($returnArray, $message[$pm]);
			}
		}
		
		return $returnArray;
	}
	
	
	/**
	 * Checks (response)-array for warnings
	 * @param string $paymentMethod - 'all', 'su' (default "all")
	 * @param (optional) array $message response array
	 * @return boolean true if warnings found ELSE false
	 */
	public function isWarning($paymentMethod = 'all', $message = '') {
		$return = false;
		
		if ($message == '') {
			$message = $this->warnings;
		}
		
		if (empty($message)) {
			$return = false;
		}
		
		if (!in_array($paymentMethod, $this->_products)) {
			$paymentMethod = 'all';
		}
		
		if ($paymentMethod == 'all') {
			$return = $this->_isErrorWarning($message);
		} else {
			if (is_array($message)) {
				if (!empty($message[$paymentMethod]) || !empty($message['global'])) {
					$return = true;
				}
			}
		}
		
		return $return;
	}
	
	
	/**
	 * Helper to iterate through an array of error or warning messages to find out wether
	 * an error/warning occured or not.
	 *
	 * @param array $message
	 * @return boolean
	 */
	private function _isErrorWarning($message) {
		if (is_array($message)) {
			foreach ($message as $errorWarning) {
				if (!empty($errorWarning)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	
	/**
	 * Returns one error message
	 * @see getErrors() for more detailed errors
	 * @param string $paymentMethod - 'all', 'sr', 'su' (default "all")
	 * @param (optional) array $message response array
	 * @return string errormessage ELSE false
	 */
	public function getError($paymentMethod = 'all', $message = '') {
		$return = false;
		
		if ($message == '') {
			$message = $this->errors;
		}
		
		if (!in_array($paymentMethod, $this->_products)) {
			$paymentMethod = 'all';
		}
		
		if (is_array($message)) {
			foreach ($message as $key => $error) {
				$errorIsArrayAndNotEmpty = is_array($error) && !empty($error);
				if ($this->_getPaymentMethodAllPmGlobal($paymentMethod, $key) && $errorIsArrayAndNotEmpty) {
					$return = 'Error: '.$error[0]['code'].':'.$error[0]['message'];
				}
			}
		}
		
		return $return;
	}
	
	
	/**
	 * Getter for errors
	 * @param string $paymentMethod - 'all', 'sr', 'su' (default "all")
	 * @param (optional) array $message response array
	 * @return emtpy array if no error exist ELSE array with error-codes and error-messages
	 */
	public function getErrors($paymentMethod = 'all', $message = '') {
		if ($message == '') {
			$message = $this->handleErrors($this->errors);
		}
		
		if (!$this->isError($paymentMethod, $message)) {
			return array();
		}
		
		$supportedPaymentMethods = $this->_products;
		
		if (!in_array($paymentMethod, $supportedPaymentMethods)) {
			$paymentMethod = 'all';
		}
		
		$returnArray = array();
		
		//return global + selected payment method
		foreach ($supportedPaymentMethods as $pm) {
			if ($this->_getPaymentMethodAllPmGlobal($paymentMethod, $pm) && array_key_exists($pm, $message)) {
				$returnArray = array_merge($returnArray, $message[$pm]);
			}
		}
		
		return $returnArray;
	}
	
	
	/**
	 * Helper function to compare given and supported Payment Method
	 *
	 * @param string $paymentMethod
	 * @param string $pm
	 * @return boolean
	 */
	private function _getPaymentMethodAllPmGlobal($paymentMethod, $pm) {
		return $paymentMethod == 'all' || $pm == 'global' || $paymentMethod == $pm;
	}
	
	
	/**
	 * Alter error array and set error message and error code together as one
	 * @param array $errors
	 * @return emtpy array if no error exist ELSE array with error-codes and error-messages
	 */
	public function handleErrors($errors) {
		$errorKeys = array_keys($errors);
		
		foreach ($errorKeys as $errorKey) {
			$i = 0;
			
			foreach ($errors[$errorKey] as $partialError) {
				if (!empty($errors[$errorKey][$i]['field']) && $errors[$errorKey][$i]['field'] !== '') {
					$errors[$errorKey][$i]['code'] .= '.'.$errors[$errorKey][$i]['field'];
				}
				
				$i++;
			};
		}
		
		return $errors;
	}
	
	
	/**
	 * Getter for the Response
	 * @return array
	 */
	public function getResponse() {
		return $this->_response;
	}
	
	
	/**
	 * Getter for the Request Data
	 * @return Ambigous
	 */
	public function getRequest() {
		return $this->_request;
	}
	
	
	/**
	 * Getter for the raw Request Data
	 */
	public function getRawRequest() {
		return $this->_DataHandler->getRawRequest();
	}
	
	
	/**
	 * Getter for the raw Response Data
	 */
	public function getRawResponse() {
		return $this->_DataHandler->getRawResponse();
	}
	
	
	/**
	 * SendRequest sends Request (array) to the DataHandler and gets Response (array)
	 */
	public function sendRequest() {
		$this->_request = $this->getData();
		$this->_DataHandler->handle($this->_request);
		$this->log(' Request -> '.$this->_DataHandler->getRequest());
		$this->_response = $this->_DataHandler->getResponse();
		$this->log(' Response -> '.$this->_DataHandler->getRawResponse());
		$this->_parse();
		$this->_handleErrors();
	}
	
	
	/**
	 * Handle Errors and Warnings occurred
	 * @return void
	 */
	protected function _handleErrors() {
		//handle errors
		if (isset($this->_response['errors']['error'])) {
			if (!isset($this->_response['errors']['error'][0])) {
				$this->errors['global'][] = $this->_getErrorBlock($this->_response['errors']['error']);
			} else {
				foreach ($this->_response['errors']['error'] as $error) {
					$this->errors['global'][] = $this->_getErrorBlock($error);
				}
			}
		}
		
		//handle warnings
		if (isset($this->_response['new_transaction']['warnings']['warning'])) {
			if (!isset($this->_response['new_transaction']['warnings']['warning'][0])) {
				$this->warnings['global'][] = $this->_getErrorBlock($this->_response['new_transaction']['warnings']['warning']);
			} else {
				foreach ($this->_response['new_transaction']['warnings']['warning'] as $warning) {
					$this->warnings['global'][] = $this->_getErrorBlock($warning);
				}
			}
		}
	}
	
	
	/**
	 * Getter for error block
	 * @param array $error
	 * @return array
	 */
	protected function _getErrorBlock($error) {
		$newError['code'] = isset($error['code']['@data']) ? $error['code']['@data'] : '';
		$newError['message'] = isset($error['message']['@data']) ? $error['message']['@data'] : '';
		$newError['field'] = isset($error['field']['@data']) ? $error['field']['@data'] : '';
		return $newError;
	}
	
	
	/**
	 * Setter for Parameter Array
	 * @param array $parameters
	 * @return SofortLibAbstract $this
	 */
	public function setParameters($parameters) {
		$this->_parameters = $parameters;
		return $this;
	}
	
	
	/**
	 * Getter for Paramterarray
	 * @return multitype
	 */
	public function getParameters() {
		return $this->_parameters;
	}
	
	
	/**
	 * Setter for Currency eg. EUR
	 * @param string $currency
	 * @return SofortLibAbstract $this
	 */
	public function setCurrencyCode($currency) {
		$this->_parameters['currency_code'] = $currency;
		return $this;
	}
	
	
	/**
	 * Setter for redirecting the success link automatically
	 * @param boolean $arg
	 * @return SofortLibAbstract $this
	 */
	public function setSuccessLinkRedirect($arg) {
		$this->_parameters['success_link_redirect'] = $arg;
		return $this;
	}
	
	
	/**
	 * The customer will be redirected to this url after a successful
	 * transaction, this should be a page where a short confirmation is
	 * displayed
	 *
	 * @param string $successUrl the url after a successful transaction
	 * @param boolean $redirect (default true)
	 * @return SofortLibAbstract $this
	 */
	public function setSuccessUrl($successUrl, $redirect = true) {
		$this->_parameters['success_url'] = $successUrl;
		$this->setSuccessLinkRedirect($redirect);
		return $this;
	}
	
	
	/**
	 * The customer will be redirected to this url if he uses the
	 * abort link on the payment form, should redirect him back to
	 * his cart or to the payment selection page
	 *
	 * @param string $abortUrl url for aborting the transaction
	 * @return SofortLibAbstract $this
	 */
	public function setAbortUrl($abortUrl) {
		$this->_parameters['abort_url'] = $abortUrl;
		return $this;
	}
	
	
	/**
	 * If the customer takes too much time or if your timeout is set too short
	 * he will be redirected to this page
	 *
	 * @param string $timeoutUrl url
	 * @return SofortLibAbstract $this
	 */
	public function setTimeoutUrl($timeoutUrl) {
		$this->_parameters['timeout_url'] = $timeoutUrl;
		return $this;
	}
	
	
	/**
	 * Set the type of notification and the adress where it should be sent to
	 * being sent to.
	 *
	 * @param string $notificationAdress email address or url
	 * @param string $notificationType (url|email)
	 * @param string $notifyOn Comma seperated (notification on: loss|pending|received|refunded|untraceable)
	 * @return SofortLibAbstract $this
	 */
	protected function _setNotification($notificationAdress, $notificationType, $notifyOn = '') {
		if ($notifyOn) {
			$notifyOnArrayIn = explode(',', $notifyOn);
			$notifyOnDefault = array('loss','pending','received','refunded', 'untraceable');
			$notifyOnArray = array();
			
			if (is_array($notifyOnArrayIn)) foreach($notifyOnArrayIn as $notifyStatus) {
				if (in_array($notifyStatus, $notifyOnDefault)) $notifyOnArray[] = $notifyStatus;
			}
			
			$notifyOn = array('notifyOn' => implode(',', $notifyOnArray));
		}
		
		if (!$notifyOn) {
			$notification = array('@data' => $notificationAdress);
		} else {
			$notification = array('@data' => $notificationAdress, '@attributes' => $notifyOn);
		}
	
		$this->_parameters['notification_'.$notificationType.'s']['notification_'.$notificationType][] = $notification;
		return $this;
	}
	
	
	/**
	 * Sets the notification Emailadress and it's attributes
	 * @param string $notification
	 * @param string $notifyOn Comma seperated (notification on: loss|pending|received|refunded|untraceable)
	 * @return SofortLibAbstract $this
	 */
	public function setNotificationEmail($notificationAdress, $notifyOn = '') {
		return $this->_setNotification($notificationAdress, 'email', $notifyOn);
	}
	
	
	/**
	 * Sets the notification URL and it's attributes
	 * @param string $notification
	 * @param string $notifyOn Comma seperated (notification on: loss|pending|received|refunded|untraceable)
	 * @return SofortLibAbstract $this
	 */
	public function setNotificationUrl($notificationAdress, $notifyOn = '') {
		return $this->_setNotification($notificationAdress, 'url', $notifyOn);
	}
	
	
	/**
	 * Setter for ConfigKey and parsing ConfigKey into userId, projectId, apiKey
	 * @param string $configKey
	 * @return SofortLibAbstract $this
	 */
	public function setConfigKey($configKey) {
		$this->_configKey = $configKey;
		list($this->_userId, $this->_projectId, $this->_apiKey) = explode(':', $configKey);
		return $this;
	}
	
	
	/**
	 * Getter for ConfigKey
	 * @return string
	 */
	public function getConfigKey() {
		return $this->_configKey;
	}
	
	
	/**
	 * Setter for the DataHandler
	 * @param AbstractDataHandler $Handler
	 * @return SofortLibAbstract $this
	 */
	public function setDataHandler(AbstractDataHandler $Handler) {
		$this->_DataHandler = $Handler;
		$this->_DataHandler->setUserId($this->_userId);
		$this->_DataHandler->setProjectId($this->_projectId);
		$this->_DataHandler->setApiKey($this->_apiKey);
		return $this;
	}
	
	
	/**
	 * Getter for the DataHandler
	 * @return void
	 */
	public function getDataHandler() {
		return $this->_DataHandler;
	}
	
	
	/**
	 * Setter for LogHandler
	 * @param AbstractLoggerHandler $Logger
	 * @return SofortLibAbstract $this
	 */
	public function setLogger(AbstractLoggerHandler $Logger) {
		$this->_Logger = $Logger;
		return $this;
	}
	
	
	/**
	 * Getter for LogHandler
	 * @return AbstractLoggerHandler
	 */
	public function getLogger() {
		return $this->_Logger;
	}
	
	
	/**
	 * Set Errors
	 * later use getError(), getErrors() or isError() to retrieve them
	 * @param string $message - Detailinformationen about the error
	 * @param string $pos - Position in the errors-array, must be one of: 'global', 'sr', 'su' (default global)
	 * @param string $errorCode - a number or string to specify the errors in the module (default -1)
	 * @param (optional) string $field - if $errorCode deals with a field
	 * @return void
	 */
	public function setError($message, $pos = 'global', $errorCode = '-1', $field = '') {
		$supportedErrorsPos = array('global', 'sr', 'su');
		
		if (!in_array($pos, $supportedErrorsPos)) {
			$pos = 'global';
		}
		
		if (!isset($this->errors[$pos])) {
			$this->errors[$pos] = array();
		}
		
		$error = array ('code' => $errorCode, 'message' => $message, 'field' => $field);
		$this->errors[$pos][] = $error;
	}
	
	
	/**
	 * Preparing Requestarray
	 * @return Ambigous <array, string>
	 */
	public function getData() {
		if (in_array($this->_rootTag, array('multipay', 'paycode'))) {
			$this->_parameters['project_id'] = $this->_projectId;
		}
		
		$requestData[$this->_rootTag] = $this->_parameters;
		$requestData = $this->_prepareRootTag($requestData);
		
		return $requestData;
	}
	
	
	/**
	 * Prepare the root tag
	 * @param array $requestData
	 * @return array
	 */
	private function _prepareRootTag($requestData) {
		if ($this->_apiVersion) {
			$requestData[$this->_rootTag]['@attributes']['version'] = $this->_apiVersion;
		}
		
		if ($this->_validateOnly) {
			$requestData[$this->_rootTag]['@attributes']['validate_only'] = 'yes';
		}
		
		return $requestData;
	}
	
	
	/**
	 * Set logging enable
	 * @return SofortLibAbstract $this
	 */
	public function setLogEnabled() {
		$this->enableLogging = true;
		return $this;
	}
	
	
	/**
	 * Set logging disabled
	 * @return SofortLibAbstract $this
	 */
	public function setLogDisabled() {
		$this->enableLogging = false;
		return $this;
	}
	
	
	/**
	 * Log the given string into warning_log.txt
	 * use $this->enableLog(); to enable logging before!
	 * @param string $message
	 * @return void
	 */
	public function logWarning($message) {
		if ($this->enableLogging) {
			$this->_Logger->log($message, 'warning');
		}
	}
	
	
	/**
	 * Log the given string into error_log.txt
	 * use $this->enableLog(); to enable logging before!
	 * @param string $message
	 * @retun void
	 */
	public function logError($message) {
		if ($this->enableLogging) {
			$this->_Logger->log($message, 'error');
		}
	}
	
	
	/**
	 * Log the given string into log.txt
	 * use $this->enableLog(); to enable logging before!
	 * @param string $message
	 * @return void
	 */
	public function log($message) {
		if ($this->enableLogging) {
			$this->_Logger->log($message, 'log');
		}
	}
	
	
	/**
	 * Parse data received or being sent
	 */
	protected function _parse() {}
}