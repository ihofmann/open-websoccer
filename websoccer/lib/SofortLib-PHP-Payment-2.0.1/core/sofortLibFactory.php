<?php
/**
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-07-24 14:28:45 +0200 (Wed, 24 Jul 2013) $
 * @version SofortLib 2.0.1  $Id: sofortLibFactory.php 243 2013-07-24 12:28:45Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class SofortLibFactory {
	
	
	/**
	 * Defines the Http Connection to be used
	 *
	 * @param string $data
	 * @param string / false $url
	 * @param array / false $headers
	 * @return SofortLibHttpCurl|SofortLibHttpSocket
	 */
	static public function getHttpConnection($data, $url = false, $headers = false) {
		if (function_exists('curl_init')) {
			require_once(dirname(__FILE__).'/sofortLibHttpCurl.inc.php');
			return new SofortLibHttpCurl($data, $url, $headers);
		} else {
			require_once(dirname(__FILE__).'/sofortLibHttpSocket.inc.php');
			return new SofortLibHttpSocket($data, $url, $headers);
		}
	}
	
	
	/**
	 * Defines and includes the logger
	 * @return FileLogger
	 */
	static public function getLogger() {
		require_once(dirname(__FILE__).'/fileLogger.php');
		return new FileLogger();
	}
	
	
	/**
	 * Defines and includes the DataHandler
	 * @param string $configKey
	 * @return XmlDataHandler
	 */
	static public function getDataHandler($configKey) {
		require_once(dirname(__FILE__).'/xmlDataHandler.php');
		return new XmlDataHandler($configKey);
	}
}