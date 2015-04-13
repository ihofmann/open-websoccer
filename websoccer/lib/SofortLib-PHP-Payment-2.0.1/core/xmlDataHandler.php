<?php
require_once(dirname(__FILE__).'/abstractDataHandler.php');
require_once(dirname(__FILE__).'/lib/xmlToArray.php');
require_once(dirname(__FILE__).'/lib/arrayToXml.php');

/**
 * Handler for XML Data
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-07-24 14:28:45 +0200 (Wed, 24 Jul 2013) $
 * @version SofortLib 2.0.1  $Id: xmlDataHandler.php 243 2013-07-24 12:28:45Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class XmlDataHandler extends AbstractDataHandler {
	
	/**
	 * Should be moved to somewhere else (where it fits better)
	 * @return void
	 */
	public function __construct($configKey) {
		parent::__construct($configKey);
	}
	
	
	/**
	 * Preparing data and parsing result received
	 * @param array $data
	 * @return void
	 */
	public function handle($data) {
		$this->_request = ArrayToXml::render($data);
		$this->_rawRequest = $this->_request;
		$xmlResponse = self::sendMessage($this->_request);
		
 		if (!in_array($this->getConnection()->getHttpStatusCode(), array('200', '301', '302'))) {
			$this->_response = array('errors' => array('error' => array('code' => array('@data' => $this->getConnection()->getHttpStatusCode()), 'message' => array('@data' => $this->getConnection()->error))));
 		} else {
			try {
				$this->_response = XmlToArray::render($xmlResponse);
			} catch (Exception $e) {
				$this->_response = array('errors' => array('error' => array('code' => array('@data' => '0999'), 'message' => array('@data' => $e->getMessage()))));
			}
 		}
		$this->_rawResponse = $xmlResponse;
 	}
	
	
	/**
	 * Sending Data to connection and returning results
	 * @param string $data
	 * @return string
	 */
	public function sendMessage($data) {
		return $this->getConnection()->post($data);
	}
}