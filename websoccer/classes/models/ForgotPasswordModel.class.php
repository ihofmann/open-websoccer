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

/**
 * @author Ingo Hofmann
 */
class ForgotPasswordModel implements IModel {
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::renderView()
	 */
	public function renderView() {
		return $this->_websoccer->getConfig("login_allow_sendingpassword");
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$parameters = array();
		if ($this->_websoccer->getConfig("register_use_captcha")
				&& strlen($this->_websoccer->getConfig("register_captcha_publickey"))
				&& strlen($this->_websoccer->getConfig("register_captcha_privatekey"))) {
			
			include_once(BASE_FOLDER . "/lib/recaptcha/recaptchalib.php");
			
			// support SSL
			$useSsl = (!empty($_SERVER["HTTPS"]));
			
			$captchaCode = recaptcha_get_html($this->_websoccer->getConfig("register_captcha_publickey"), null, $useSsl);
			

			$parameters["captchaCode"] = $captchaCode;
		}
		
		return $parameters;
	}
	
}

?>