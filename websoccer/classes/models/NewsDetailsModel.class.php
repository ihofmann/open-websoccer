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
class NewsDetailsModel implements IModel {
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
		$tablePrefix = $this->_websoccer->getConfig("db_prefix") . "_";
		$fromTable = $tablePrefix . "news AS NewsTab";
		$fromTable .= " LEFT JOIN " . $tablePrefix . "admin AS AdminTab ON NewsTab.autor_id = AdminTab.id";
		$whereCondition = "NewsTab.id = %d AND status = 1";
		$parameters = (int) $this->_websoccer->getRequestParameter("id");
		
		$result = $this->_db->querySelect("NewsTab.*, AdminTab.name AS author_name", $fromTable, $whereCondition, $parameters);
		$item = $result->fetch_array();
		$result->free();
		
		if (!$item) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		// convert message
		$message = $item["nachricht"];
		if ($item["c_br"]) {
			$message = nl2br($message);
		}
		if ($item["c_links"]) {
			$message = $this->_strToLink($message);
		}
		
		// related links
		$relatedLinks = array();
		
		if ($item["linktext1"] && $item["linkurl1"]) {
			$relatedLinks[$item["linkurl1"]] = $item["linktext1"];
		}
		if ($item["linktext2"] && $item["linkurl2"]) {
			$relatedLinks[$item["linkurl2"]] = $item["linktext2"];
		}
		if ($item["linktext3"] && $item["linkurl3"]) {
			$relatedLinks[$item["linkurl3"]] = $item["linktext3"];
		}
		
		$article = array("id" => $item["id"],
				"title" => $item["titel"],
				"date" => $this->_websoccer->getFormattedDate($item["datum"]),
				"message" => $message,
				"author_name" => $item["author_name"]);
		
		return array("article" => $article, "relatedLinks" => $relatedLinks);
	}
	
	private function _strToLink($str) {

	  //URL
	  $str = preg_replace("#([\t\r\n ])([a-z0-9]+?){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)#i", 
	  			'\1<a href="\2://\3" target="_blank">\2://\3</a>', $str);
	
	  //EMail
	  $str = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", 
	  		"\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $str);
	
	  return $str;
	
	}
	
}

?>