<?php
/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it 
  and/or modify it under the terms of the 
  GNU Lesser General Public License 
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public 
  License along with OpenWebSoccer-Sim.  
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/

require_once(BASE_FOLDER . '/classes/googleapi/Google_Client.php');
require_once(BASE_FOLDER . '/classes/googleapi/contrib/Google_Oauth2Service.php');

/**
 * Provides access to the Google+ API.
 * 
 * @author Ingo Hofmann
 */
class GoogleplusSdk {

	private static $_instance;
	
	private $_client;
	private $_websoccer;
	private $_oauth2Service;
	
	/**
	 * Creates or returns the only instance of this class.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @return GoogleplusSdk the only instance during current request.
	 */
	public static function getInstance(WebSoccer $websoccer) {
		if(self::$_instance == NULL) {
			self::$_instance = new GoogleplusSdk($websoccer);
		}
		return self::$_instance;
	}
	
	// hide constructor (Singleton implementation)
	// inits SDK.
	private function __construct(WebSoccer $websoccer) {
		$this->_websoccer = $websoccer;
		
		$client = new Google_Client();
		$client->setApplicationName($this->_websoccer->getConfig('googleplus_appname'));
		// Visit https://code.google.com/apis/console?api=plus to generate your
		// client id, client secret, and to register your redirect uri.
		$client->setClientId($this->_websoccer->getConfig('googleplus_clientid'));
		$client->setClientSecret($this->_websoccer->getConfig('googleplus_clientsecret'));
		$client->setRedirectUri($this->_websoccer->getInternalActionUrl('googleplus-login', null, 'home', TRUE));
		$client->setDeveloperKey($this->_websoccer->getConfig('googleplus_developerkey'));
		
		$client->setScopes(array('https://www.googleapis.com/auth/plus.login', 'https://www.googleapis.com/auth/userinfo.email'));
		
		// service must be registered before authentication!
		$this->_oauth2Service = new Google_Oauth2Service($client);
		
		$this->_client = $client;
	}
	
	/**
	 * Build secure log-in URL which forwards user to Google+ in order to connect to application.
	 * 
	 * @return string Log-In URL.
	 */
	public function getLoginUrl() {
		return $this->_client->createAuthUrl();
	}
	
	/**
	 * Tries to authenticate the current user and provides his e-mail address on success.
	 * 
	 * @return FALSE|string FALSE if user could not be authenticated. Otherwise, the user's e-mail address at Google+.
	 */
	public function authenticateUser() {
		
		if (isset($_GET['code'])) {
			$this->_client->authenticate();
			$_SESSION['gptoken'] = $this->_client->getAccessToken();
		}
		
		if (isset($_SESSION['gptoken'])) {
			$this->_client->setAccessToken($_SESSION['gptoken']);
		}
		
		// provide e-mail of user
		if ($this->_client->getAccessToken()) {
			$userinfo = $this->_oauth2Service->userinfo->get();
			$email = $userinfo['email'];
			
			// update cached token
			$_SESSION['gptoken'] = $this->_client->getAccessToken();
			
			if (strlen($email)) {
				return $email;
			}
		}
		
		return FALSE;
	}
	
}
?>