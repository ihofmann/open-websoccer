<?php
require_once(dirname(__FILE__).'/sofortLibHttp.inc.php');

/**
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-07-24 14:28:45 +0200 (Wed, 24 Jul 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibHttpCurl.inc.php 243 2013-07-24 12:28:45Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class SofortLibHttpCurl extends SofortLibHttp {
	
	/**
	 * Send data to server with POST request
	 * @param string $data
	 * @param (optional) string $url
	 * @param (optional) string $headers
	 * @return string
	 */
	public function post($data, $url = false, $headers = false) {
		$this->connectionMethod = 'cURL';
		
		if ($url === false) {
			$url = $this->url;
		}
		
		if ($headers === false) {
			$headers = $this->headers;
		}
		
		$curlOpt = array();
		
		$headers[] = 'User-Agent: SofortLib-php/'.SOFORTLIB_VERSION.'-'.$this->connectionMethod;
		
		$curlOpt[CURLOPT_HTTPHEADER] = array_merge($headers, array('Expect:'));
		//print_r($curlOpt[CURLOPT_HTTPHEADER]); die();
		$curlOpt[CURLOPT_POST] = 1;
		$curlOpt[CURLOPT_HEADER] = false;
		
		if ($this->compression !== false) {
			$curlOpt[CURLOPT_ENCODING] = $this->compression;
		}
		
		$curlOpt[CURLOPT_TIMEOUT] = 15;
		
		if ($this->proxy) {
			$curlOpt[CURLOPT_PROXY] = $this->proxy;
		}
		
		$curlOpt[CURLOPT_POSTFIELDS] = $data;
		$curlOpt[CURLOPT_RETURNTRANSFER] = 1;
		$curlOpt[CURLOPT_SSL_VERIFYHOST] = 0;
		$curlOpt[CURLOPT_SSL_VERIFYPEER] = false;
		
		$return = $this->_curlRequest($url, $curlOpt);
		
		if ($this->error) {
			return $this->_xmlError('00'.$this->error, $this->_response);
		}
		
		return $return;
	}
	
	
	/**
	 * Post data using curl
	 * @param string $url
	 * @param (optional) array $curlOpt
	 * @return string
	 */
	protected function _curlRequest($url, $curlOpt = array()) {
		$process = curl_init($url);
		
		foreach ($curlOpt as $curlKey => $curlValue) {
			curl_setopt($process, $curlKey, $curlValue);
		}
		
		$return = curl_exec($process);
		$this->info = curl_getinfo($process);
		$this->error = curl_error($process);
		$this->httpStatus = $this->info['http_code'];
		$this->_response = $return;
		curl_close($process);
		return $return;
	}
}