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
define("NUMBER_OF_TOP_NEWS", 5);

/**
 * @author Ingo Hofmann
 */
class TopNewsListModel implements IModel {
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
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_news";
		
		// select
		$columns = "id, titel, datum";
		$whereCondition = "status = 1 ORDER BY datum DESC";
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, array(), NUMBER_OF_TOP_NEWS);
		
		$articles = array();
		while ($article = $result->fetch_array()) {
			$articles[] = array("id" => $article["id"],
								"title" => $article["titel"],
								"date" => $this->_websoccer->getFormattedDate($article["datum"]));
		}
		$result->free();
		
		return array("topnews" => $articles);
	}
	
	
}

?>