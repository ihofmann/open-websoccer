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
class ProfileModel implements IModel {
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
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_user";
		
		$user = $this->_websoccer->getUser();
		
		// select
		$columns["name"] = "realname";
		$columns["wohnort"] = "place";
		$columns["land"] = "country";
		$columns["geburtstag"] = "birthday";
		$columns["beruf"] = "occupation";
		$columns["interessen"] = "interests";
		$columns["lieblingsverein"] = "favorite_club";
		$columns["homepage"] = "homepage";
		$columns["c_hideinonlinelist"] = "c_hideinonlinelist";
		
		$whereCondition = "id = %d";
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $user->id, 1);
		$userinfo = $result->fetch_array();
		$result->free();
		
		if (!strlen($userinfo["birthday"]) || substr($userinfo["birthday"], 0, 4) == "0000") {
			$userinfo["birthday"] = "";
		} else {
			$userinfo["birthday"] = DateTime::createFromFormat("Y-m-d", $userinfo["birthday"])->format($this->_websoccer->getConfig("date_format"));
		}
		
		foreach ($columns as $dbColumn) {
			if ($this->_websoccer->getRequestParameter($dbColumn)) {
				$userinfo[$dbColumn] = $this->_websoccer->getRequestParameter($dbColumn);
			}
		}
		
		return array("user" => $userinfo);
	}
	
}
?>