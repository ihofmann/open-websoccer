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
class TicketsModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return TRUE;
	}
	
	public function getTemplateParameters() {
		
		$teamId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		$columns["T.preis_stehen"] = "p_stands";
		$columns["T.preis_sitz"] = "p_seats";
		$columns["T.preis_haupt_stehen"] = "p_stands_grand";
		$columns["T.preis_haupt_sitze"] = "p_seats_grand";
		$columns["T.preis_vip"] = "p_vip";
		
		$columns["T.last_steh"] = "l_stands";
		$columns["T.last_sitz"] = "l_seats";
		$columns["T.last_haupt_steh"] = "l_stands_grand";
		$columns["T.last_haupt_sitz"] = "l_seats_grand";
		$columns["T.last_vip"] = "l_vip";
		
		$columns["S.p_steh"] = "s_stands";
		$columns["S.p_sitz"] = "s_seats";
		$columns["S.p_haupt_steh"] = "s_stands_grand";
		$columns["S.p_haupt_sitz"] = "s_seats_grand";
		$columns["S.p_vip"] = "s_vip";
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_verein AS T";
		$fromTable .= " LEFT JOIN " . $this->_websoccer->getConfig("db_prefix") . "_stadion AS S ON S.id = T.stadion_id";
		$whereCondition = "T.id = %d";
		$parameters = $teamId;
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$tickets = $result->fetch_array();
		$result->free();
		
		if ($this->_websoccer->getRequestParameter("p_stands")) {
			$tickets["p_stands"] =  $this->_websoccer->getRequestParameter("p_stands");
		}
		if ($this->_websoccer->getRequestParameter("p_seats")) {
			$tickets["p_seats"] =  $this->_websoccer->getRequestParameter("p_seats");
		}
		if ($this->_websoccer->getRequestParameter("p_stands_grand")) {
			$tickets["p_stands_grand"] =  $this->_websoccer->getRequestParameter("p_stands_grand");
		}
		if ($this->_websoccer->getRequestParameter("p_seats_grand")) {
			$tickets["p_seats_grand"] =  $this->_websoccer->getRequestParameter("p_seats_grand");
		}
		if ($this->_websoccer->getRequestParameter("p_vip")) {
			$tickets["p_vip"] =  $this->_websoccer->getRequestParameter("p_vip");
		}
		
		return array("tickets" => $tickets);
	}
	
}

?>