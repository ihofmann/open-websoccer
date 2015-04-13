<?php
/**
 * Abstract Data Handler
 * Handles the SofortLibs data-input and output. For the moment we only
 * offer an XML-Handler, others (i.e. JSON) are possible too.
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-07-24 14:28:45 +0200 (Wed, 24 Jul 2013) $
 * @version SofortLib 2.0.1  $Id: abstractDataHandler.php 243 2013-07-24 12:28:45Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
abstract class AbstractDataHandler {
	
	/**
	 * Contains the Raw Request Data
	 *
	 * @var array, string
	 */
	protected $_rawRequest = '';
	
	/**
	 * Contains the request Data, that has been sent to the API
	 * @var array
	 */
	protected $_request = array();
	
	/**
	 * Provides the naked response returned by the API or (if no answer was received
	 * an Error Code).
	 * @var array, string
	 */
	protected $_rawResponse = '';
	
	/**
	 * Provides the parsed response.
	 * @var array
	 */
	protected $_response = array();
	
	/**
	 * Object for the type of the connection, HTTP, others might follow
	 * @var object
	 */
	protected $_Connection = null;	// http instance
	
	/**
	 * Object for the Logging.
	 * @var object
	 */
	protected $_Logger = null;	// Logger instance
	
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
	 * Constructor for AbstractDataHandler
	 * @param string $configKey
	 * @return void
	 */
	public function __construct($configKey) {
		$this->setConfigKey($configKey);
	}
	
	
	abstract function handle($data);
	
	
	abstract function sendMessage($data);
	
	
	/**
	 * Setting the connection (standard: http instance) and the configkey
	 * @param string $Connection
	 * @return AbstractDataHandler $this
	 */
	public function setConnection($Connection) {
		$this->_Connection = $Connection;
		$this->_Connection->setConfigKey($this->_configKey);
		return $this;
	}
	
	
	/**
	 * Returns the connection, normally a http instance
	 * @return Connection Object
	 */
	public function getConnection() {
		return $this->_Connection;
	}
	
	
	/**
	 * Setting the configKey and extracting userId, projectId and apiKey from configKey
	 * @param string $configKey
	 * @return AbstractDataHandler $this
	 */
	public function setConfigKey($configKey) {
		$this->_configKey = $configKey;
		list($this->_userId, $this->_projectId, $this->_apiKey) = explode(':', $configKey);
		return $this;
	}
	
	
	/**
	 * Setter for the userId
	 * @param string $userId
	 * @return AbstractDataHandler $this
	 */
	public function setUserId($userId) {
		$this->_userId = $userId;
		return $this;
	}
	
	
	/**
	 * Getter for the userId
	 * @return string
	 */
	public function getUserId() {
		return $this->_userId;
	}
	
	
	/**
	 * Setter for the userId
	 * @param string $projectId
	 * @return AbstractDataHandler $this
	 */
	public function setProjectId($projectId) {
		$this->_projectId = $projectId;
		return $this;
	}
	
	
	/**
	 * Getter for the ProjectId
	 * @return string
	 */
	public function getProjectId() {
		return $this->_projectId;
	}
	
	
	/**
	 * Setter for the ApiKey
	 * @param string $apiKey
	 * @return AbstractDataHandler $this
	 */
	public function setApiKey($apiKey) {
		$this->_apiKey = $apiKey;
		return $this;
	}
	
	
	/**
	 * Getter for the ApiKey
	 * @return string
	 */
	public function getApiKey() {
		return $this->_apiKey;
	}
	
	
	/**
	 * Getter for the Response
	 * @return multitype:
	 */
	public function getResponse() {
		return $this->_response;
	}
	
	
	/**
	 * Getter for the raw Response Data
	 * @return string
	 */
	public function getRawResponse() {
		return $this->_rawResponse;
	}
	
	
	/**
	 * Getter for the Request
	 * @return multitype:
	 */
	public function getRequest() {
		return $this->_request;
	}
	
	
	/**
	 * Getter for the raw Request Data
	 * @return string
	 */
	public function getRawRequest() {
		return $this->_rawRequest;
	}
}
?>