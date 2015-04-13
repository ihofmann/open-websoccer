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
 * Provides players of team with passed ID.
 */
class TeamPlayersModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_teamid;
	
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
		$this->_teamid = (int) $this->_websoccer->getRequestParameter("teamid");
		return ($this->_teamid > 0);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$isNationalTeam = ($this->_websoccer->getRequestParameter("nationalteam")) ? TRUE : FALSE;
		$players = PlayersDataService::getPlayersOfTeamById($this->_websoccer, $this->_db, $this->_teamid, $isNationalTeam);
		
		return array("players" => $players);
	}
	
}

?>