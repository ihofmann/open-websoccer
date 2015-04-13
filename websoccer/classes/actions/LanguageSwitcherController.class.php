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

class LanguageSwitcherController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		$lang = strtolower($parameters["lang"]);
		
		$this->_i18n->setCurrentLanguage($lang);
		
		// update user profile
		$user = $this->_websoccer->getUser();
		if ($user->id != null) {
			$fromTable = $this->_websoccer->getConfig("db_prefix") ."_user";
			$columns = array("lang" => $lang);
			$whereCondition = "id = %d";
			$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $user->id);
		}
		
		// re-include messages in order to update UI immediately
		global $msg;
		$msg = array();
		include(sprintf(CONFIGCACHE_MESSAGES, $lang));
		include(sprintf(CONFIGCACHE_ENTITYMESSAGES, $lang));
		
		
		return null;
	}
	
}

?>