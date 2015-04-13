<?php
/**
 * Encapsulates communication via HTTP
 *
 * requires libcurl and openssl
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-09-13 12:45:31 +0200 (Fri, 13 Sep 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibHttp.inc.php 269 2013-09-13 10:45:31Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class SofortLibHttp {
	
	/**
	 * Headers to be sent
	 * @var array
	 */
	public $headers;
	
	/**
	 * Compression on/off?
	 * @var boolean
	 */
	public $compression;
	
	/**
	 * Proxy to be used
	 * @var string
	 */
	public $proxy;
	
	/**
	 * API-url
	 * @var string
	 */
	public $url;
	
	/**
	 * Informations for the last transfer
	 * @var mixed
	 */
	public $info;
	
	/**
	 * Error Code and Description
	 * @var array
	 */
	public $error;
	
	/**
	 * HTTP-Status Code
	 * @var integer
	 */
	public $httpStatus = 200;
	
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
	 * Provides the parsed response.
	 * @var string
	 */
	protected $_response = '';
	
	/**
	 * Complete Config Key as provided in User Account on sofort.com
	 * @var string
	 */
	protected $_configKey = '';
	
	
	/**
	 * Constructor for SofortLibHttp
	 * @param string $url
	 * @param boolean $compression (default false)
	 * @param (optional) string $proxy
	 * @return void
	 */
	public function __construct($url, $compression = false, $proxy = '') {
		$this->url = $url;
		$this->compression = $compression;
		$this->proxy = $proxy;
	}
	
	
	/**
	 * Setter for ConfigKey and parsing ConfigKey into userId, ProjectId, apiKey
	 * @param string $configKey
	 * @return void
	 */
	public function setConfigKey($configKey) {
		$this->_configKey = $configKey;
		list($this->_userId, $this->_projectId, $this->_apiKey) = explode(':', $configKey);
		$this->setHeaders();
	}
	
	
	/**
	 * Setting Headers to be sent
	 * @return void
	 */
	public function setHeaders() {
		$header[] = 'Authorization: Basic '.base64_encode($this->_userId.':'.$this->_apiKey);
		$header[] = 'Content-Type: application/xml; charset=UTF-8';
		$header[] = 'Accept: application/xml; charset=UTF-8';
		$header[] = 'X-Powered-By: PHP/'.phpversion();
		$this->headers = $header;
	}
	
	
	/**
	 * Getter for information
	 * @param (optional) string $opt
	 * @return string
	 */
	public function getInfo($opt = '') {
		if (!empty($opt)) {
			return $this->info[$opt];
		} else {
			return $this->info;
		}
	}
	
	
	/**
	 * HTTP error handling
	 * @return array(code, message, response[if available])
	 */
	public function getHttpCode() {
		switch ($this->httpStatus) {
			case(200):
				return array('code' => 200, 'message' => $this->_xmlError($this->httpStatus, 'OK'), 'response' => $this->_response);
				break;
			case(301):
			case(302):
				return array('code' => $this->httpStatus, 'message' => $this->_xmlError($this->httpStatus, 'Redirected Request'), 'response' => $this->_response);
				break;
			case(401):
				$this->error = 'Unauthorized';
				return array('code' => 401, 'message' => $this->_xmlError($this->httpStatus, $this->error), 'response' => $this->_response);
				break;
			case(0):
			case(404):
				$this->httpStatus = 404;
				$this->error = 'URL not found '.$this->url;
				return array('code' => 404, 'message' => $this->_xmlError($this->httpStatus, $this->error), 'response' => '');
				break;
			case(500):
				$this->error = 'An error occurred';
				return array('code' => 500, 'message' => $this->_xmlError($this->httpStatus, $this->error), 'response' => $this->_response);
				break;
			default:
				$this->error = 'Something went wrong, not handled httpStatus';
				return array('code' => $this->httpStatus, 'message' => $this->_xmlError($this->httpStatus, $this->error), 'response' => $this->_response);
				break;
		}
	}
	
	
	/**
	 * Getter for HTTP status code
	 * @return string
	 */
	public function getHttpStatusCode() {
		$status = $this->getHttpCode();
		return $status['code'];
	}
	
	
	/**
	 * Getter for HTTP status message
	 * @return string
	 */
	public function getHttpStatusMessage() {
		$status = $this->getHttpCode();
		return $status['message'];
	}
	
	
	/**
	 * Output an xml error
	 * @param string $code
	 * @param string $message
	 * @return string xml error
	 */
	protected function _xmlError($code, $message) {
		return '<errors><error><code>0'.$code.'</code><message>'.$message.'</message></error></errors>';
	}
}
?>