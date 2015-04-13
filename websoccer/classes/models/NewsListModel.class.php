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
define("NEWS_ENTRIES_PER_PAGE", 5);
define("NEWS_TEASER_MAXLENGTH", 256);

/**
 * @author Ingo Hofmann
 */
class NewsListModel implements IModel {
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
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_news";
		$whereCondition = "status = %d";
		$parameters = "1";
		
		// count items for pagination
		$result = $this->_db->querySelect("COUNT(*) AS hits", $fromTable, $whereCondition, $parameters);
		$rows = $result->fetch_array();
		$result->free();
		
		// enable paginations
		$eps = NEWS_ENTRIES_PER_PAGE;
		$paginator = new Paginator($rows["hits"], $eps, $this->_websoccer);
		
		// select
		$columns = "id, titel, datum, nachricht";
		$whereCondition .= " ORDER BY datum DESC";
		$limit = $paginator->getFirstIndex() .",". $eps;
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters, $limit);
		
		$articles = array();
		while ($article = $result->fetch_array()) {
			$articles[] = array("id" => $article["id"],
								"title" => $article["titel"],
								"date" => $this->_websoccer->getFormattedDate($article["datum"]),
								"teaser" => $this->_shortenMessage($article["nachricht"]));
		}
		$result->free();
		
		return array("articles" => $articles, "paginator" => $paginator);
	}
	
	private function _shortenMessage($message) {
		if (strlen($message) > NEWS_TEASER_MAXLENGTH) {
			$message = wordwrap($message, NEWS_TEASER_MAXLENGTH);
			$message = substr($message, 0, strpos($message, "\n")) . "...";
		}
		return $message;
	}
	
}

?>