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
 * Provides completed seasons and completed cup competitions.
 */
class HallOfFameModel implements IModel {
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
		
		$leagues = array();
		$cups = array();
		
		// get seasons
		$columns = array(
				'L.name' => 'league_name',
				'L.land' => 'league_country',
				'S.name' => 'season_name',
				'C.id' => 'team_id',
				'C.name' => 'team_name',
				'C.bild' => 'team_picture'
				);
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_saison AS S';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_liga AS L ON L.id = S.liga_id';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_verein AS C ON C.id = S.platz_1_id';
		$whereCondition = 'S.beendet = \'1\' ORDER BY L.land ASC, L.name ASC, S.id DESC';
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition);
		while ($season = $result->fetch_array()) {
			$leagues[$season['league_name']][] = $season;
		}
		$result->free();
		
		// cups
		$columns = array(
				'CUP.name' => 'cup_name',
				'C.id' => 'team_id',
				'C.name' => 'team_name',
				'C.bild' => 'team_picture'
		);
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_cup AS CUP';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_verein AS C ON C.id = CUP.winner_id';
		$whereCondition = 'CUP.winner_id IS NOT NULL ORDER BY CUP.id DESC';
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition);
		while ($cup = $result->fetch_array()) {
			$cups[] = $cup;
		}
		$result->free();
		
		return array('leagues' => $leagues, 'cups' => $cups);
	}
	
}

?>