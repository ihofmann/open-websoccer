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
class PlayerStatisticsModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
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
		
		$playerId = (int) $this->_websoccer->getRequestParameter('id');
		if ($playerId < 1) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		// query statistics
		$leagueStatistics = array();
		$cupStatistics = array();
		
		$columns = array(
			'L.name' => 'league_name',	
			'SEAS.name' => 'season_name',
			'M.pokalname' => 'cup_name',
			'COUNT(S.id)' => 'matches',
			'SUM(S.assists)' => 'assists',
			'AVG(S.note)' => 'grade',
			'SUM(S.tore)' => 'goals',
			'SUM(S.karte_gelb)' => 'yellowcards',
			'SUM(S.karte_rot)' => 'redcards',
			'SUM(S.shoots)' => 'shoots',
			'SUM(S.passes_successed)' => 'passes_successed',
			'SUM(S.passes_failed)' => 'passes_failed'
		);
		
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_spiel_berechnung AS S';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_spiel AS M ON M.id = S.spiel_id';
		$fromTable .= ' LEFT JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_saison AS SEAS ON SEAS.id = M.saison_id';
		$fromTable .= ' LEFT JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_liga AS L ON SEAS.liga_id = L.id';
		
		$whereCondition = 'S.spieler_id = %d AND S.minuten_gespielt > 0 AND ((M.spieltyp = \'Pokalspiel\' AND M.pokalname IS NOT NULL AND M.pokalname != \'\') OR (M.spieltyp = \'Ligaspiel\' AND SEAS.id IS NOT NULL)) GROUP BY IFNULL(M.pokalname,\'\'), SEAS.id ORDER BY L.name ASC, SEAS.id ASC, M.pokalname ASC';		
		
		// execute
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $playerId);
		while ($statistic = $result->fetch_array()) {
			if (strlen($statistic['league_name'])) {
				$leagueStatistics[] = $statistic;
			} else {
				$cupStatistics[] = $statistic;
			}
		}
		$result->free();
		
		return array('leagueStatistics' => $leagueStatistics, 'cupStatistics' => $cupStatistics);
	}
	
}

?>