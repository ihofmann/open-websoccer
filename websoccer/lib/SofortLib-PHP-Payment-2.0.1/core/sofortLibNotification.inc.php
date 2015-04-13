<?php
require_once(dirname(__FILE__).'/lib/xmlToArray.php');

/**
 * This class handels incoming notifications for sofortueberweisung and invoice
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-07-24 14:28:45 +0200 (Wed, 24 Jul 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibNotification.inc.php 243 2013-07-24 12:28:45Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class SofortLibNotification {
	
	/**
	 * Array for the errors that occured
	 * @var array
	 */
	public $errors = array();
	
	/**
	 * Containter for the returned transaction id
	 * @var String
	 */
	private $_transactionId = '';
	
	/**
	 * Container for the returned timestamp
	 * @var Datetime
	 */
	private $_time;
	
	
	/**
	 * Reads the input and tries to read the transaction id
	 *
	 * @param string $content XML-File Content
	 * @return boolean|string (transaction ID, when true)
	 */
	public function getNotification($content) {
		try {
			$response = XmlToArray::render($content);
		} catch (Exception $e) {
			$this->errors['error']['message'] = 'could not parse message';
			return false;
		}
		
		if (!isset($response['status_notification'])) {
			return false;
		}
		
		if (isset($response['status_notification']['transaction']['@data'])) {
			$this->_transactionId = $response['status_notification']['transaction']['@data'];
			
			if ($response['status_notification']['time']['@data']) {
				$this->_time = $response['status_notification']['time']['@data'];
			}
			
			return $this->_transactionId;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Getter for variable time
	 * @return string
	 */
	public function getTime() {
		return $this->_time;
	}
	
	
	/**
	 * Getter for transaction
	 * @return string
	 */
	public function getTransactionId() {
		return $this->_transactionId;
	}
}