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
 * Provides list of unsimulated matches of user.
 */
class MyScheduleModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_teamId;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::renderView()
	 */
	public function renderView() {
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$matches = array();
		$paginator = null;
		
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		
		$whereCondition = '(home_verein = %d OR gast_verein = %d) AND berechnet != \'1\'';
		$parameters = array($clubId, $clubId);
		
		$result = $this->_db->querySelect('COUNT(*) AS hits', $this->_websoccer->getConfig('db_prefix') . '_spiel', $whereCondition, $parameters);
		$matchesCnt = $result->fetch_array();
		$result->free();
		if ($matchesCnt) {
			$count = $matchesCnt['hits'];
		} else {
			$count = 0;
		}
		
		if ($count) {
			$whereCondition .= ' ORDER BY M.datum ASC';
			$eps = $this->_websoccer->getConfig("entries_per_page");
			$paginator = new Paginator($count, $eps, $this->_websoccer);
			
			$matches = MatchesDataService::getMatchesByCondition($this->_websoccer, $this->_db, $whereCondition, $parameters, 
					$paginator->getFirstIndex() . ',' . $eps);
			
		}
		
		return array("matches" => $matches, "paginator" => $paginator);
	}
	
}

?>