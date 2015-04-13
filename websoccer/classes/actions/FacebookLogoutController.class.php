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
 * Controller that displays a message according to whether Facebook Log-out has been successful or not.
 */
class FacebookLogoutController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		
		// user could not be logged out
		if (strlen(FacebookSdk::getInstance($this->_websoccer)->getUserEmail())) {
			
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_ERROR,
					$this->_i18n->getMessage("facebooklogout_failure"),
					""));
			
			return null;
		}
		
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
					$this->_i18n->getMessage("facebooklogout_success"),
					$this->_i18n->getMessage("facebooklogout_success_details")));
		
		return "home";
	}
	
}

?>