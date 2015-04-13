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

class SaveTicketsController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		
		$user = $this->_websoccer->getUser();
		
		$clubId = $user->getClubId($this->_websoccer, $this->_db);
		if (!$clubId) {
			return null;
		}
		
		$columns["preis_stehen"] = $parameters["p_stands"];
		$columns["preis_sitz"] = $parameters["p_seats"];
		$columns["preis_haupt_stehen"] = $parameters["p_stands_grand"];
		$columns["preis_haupt_sitze"] = $parameters["p_seats_grand"];
		$columns["preis_vip"] = $parameters["p_vip"];
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_verein";
		$whereCondition = "id = %d";
		$parameters = $clubId;
		
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameters);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
				$this->_i18n->getMessage("saved_message_title"),
				""));
		
		return null;
	}
	
}

?>