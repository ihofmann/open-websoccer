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

class FinancesSummaryModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_teamId;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_teamId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
	}
	
	public function renderView() {
		return ($this->_teamId > 0);
	}
	
	public function getTemplateParameters() {
		
		$minDate = $this->_websoccer->getNowAsTimestamp() - 365 * 3600 * 24;
		
		$columns = array(
				'verwendung' => 'subject',
				'SUM(betrag)' => 'balance',
				'AVG(betrag)' => 'avgAmount'
				);
		$result = $this->_db->querySelect($columns, 
				$this->_websoccer->getConfig('db_prefix') . '_konto', 
				'verein_id = %d AND datum > %d GROUP BY verwendung HAVING COUNT(*) > 5', array($this->_teamId, $minDate));
		$majorPositions = array();
		while ($position = $result->fetch_array()) {
			$majorPositions[] = $position;
		}
		$result->free();
		
		return array('majorPositions' => $majorPositions);
	}
	
}

?>