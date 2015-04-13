<?php
require_once(dirname(__FILE__).'../../core/sofortLibMultipay.inc.php');

/**
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-04-17 11:19:37 +0200 (Wed, 17 Apr 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibSofortueberweisung.inc.php 116 2013-04-17 09:19:37Z dehn $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class Sofortueberweisung extends SofortLibMultipay {
	
	
	/**
	 * Constructor for Sofortueberweisung
	 * @param string $configKey
	 * @return void
	 */
	public function __construct($configKey) {
		parent::__construct($configKey);
		$this->_parameters['su'] = array();
	}
	
	
	/**
	 * Setter for Customer Protection
	 * if possible for customers
	 * @param boolean $customerProtection (default true)
	 * @return Sofortueberweisung $this
	 */
	public function setCustomerprotection($customerProtection = true) {
		if (!array_key_exists('su', $this->_parameters) || !is_array($this->_parameters['su'])) {
			$this->_parameters['su'] = array();
		}
		
		$this->_parameters['su']['customer_protection'] = $customerProtection ? 1 : 0;
		return $this;
	}
	
	
	/**
	 * Handle Errors occurred
	 * @return void
	 */
	protected function _handleErrors() {
		parent::_handleErrors();
		
		//handle errors
		if (isset($this->_response['errors']['su'])) {
			if (!isset($this->_response['errors']['su']['errors']['error'][0])) {
				$tmp = $this->_response['errors']['su']['errors']['error'];
				unset($this->_response['errors']['su']['errors']['error']);
				$this->_response['errors']['su']['errors']['error'][0] = $tmp;
			}
			
			foreach ($this->_response['errors']['su']['errors']['error'] as $error) {
				$this->errors['su'][] = $this->_getErrorBlock($error);
			}
		}
		
		//handle warnings
		if (isset($this->_response['new_transaction']['warnings']['su'])) {
			if (!isset($this->_response['new_transaction']['warnings']['su']['warnings']['warning'][0])) {
				$tmp = $this->_response['new_transaction']['warnings']['su']['warnings']['warning'];
				unset($this->_response['new_transaction']['warnings']['su']['warnings']['warning']);
				$this->_response['new_transaction']['warnings']['su']['warnings']['warning'][0] = $tmp;
			}
			
			foreach ($this->_response['new_transaction']['warnings']['su']['warnings']['warning'] as $warning) {
				$this->warnings['su'][] = $this->_getErrorBlock($warning);
			}
		}
	}
}