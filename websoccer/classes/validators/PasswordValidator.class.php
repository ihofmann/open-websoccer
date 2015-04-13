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
 * Validates passwords.
 * 
 * @author Ingo Hofmann
 */
class PasswordValidator implements IValidator {
	private $_i18n;
	private $_websoccer;
	private $_value;
	
	/**
	 * @param I18n $i18n i18n instance.
	 * @param WebSoccer $websoccer Websoccer instance.
	 * @param mixed $value value to be validated.
	 */
	public function __construct($i18n, $websoccer, $value) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_value = $value;
	}
	
	/**
	 * @see IValidator::isValid()
	 */
	public function isValid() {
		
		// must contain at least one letter and one number
		if (!preg_match('/[A-Za-z]/', $this->_value) || !preg_match('/[0-9]/', $this->_value)) {
			return FALSE;
		}
		
		// validate against blacklist. 
		// see also http://splashdata.com/splashid/worst-passwords/index.htm
		$blacklist = array('test123', 'abc123', 'passw0rd', 'passw0rt');
		if (in_array(strtolower($this->_value), $blacklist)) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * @see IValidator::getMessage()
	 */
	public function getMessage() {
		return $this->_i18n->getMessage('validation_error_password');
	}
	
}

?>