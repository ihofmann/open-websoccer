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
class LeagueSelectionModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_country;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		$this->_country = $this->_websoccer->getRequestParameter("country");
		return (strlen($this->_country));
	}
	
	public function getTemplateParameters() {
		
		// get table markers
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_liga";
		$whereCondition = "land = '%s' ORDER BY name ASC";
		
		$leagues = array();
		
		$result = $this->_db->querySelect("id, name", $fromTable, $whereCondition, $this->_country);
		while ($league = $result->fetch_array()) {
			$leagues[] = $league;
		}
		$result->free();
		
		return array("leagues" => $leagues);
	}
	
	
}

?>