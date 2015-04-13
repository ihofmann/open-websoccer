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
class PlayersSearchModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	private $_firstName;
	private $_lastName;
	private $_club;
	private $_position;
	private $_strength;
	private $_lendableOnly;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		$this->_firstName = $this->_websoccer->getRequestParameter("fname");
		$this->_lastName = $this->_websoccer->getRequestParameter("lname");
		$this->_club = $this->_websoccer->getRequestParameter("club");
		$this->_position = $this->_websoccer->getRequestParameter("position");
		$this->_strength = $this->_websoccer->getRequestParameter("strength");
		$this->_lendableOnly = ($this->_websoccer->getRequestParameter("lendable") == "1") ? TRUE : FALSE;
		
		// display content only if user entered any filter
		return ($this->_firstName !== null || $this->_lastName !== null
				|| $this->_club !== null || $this->_position !== null
				|| $this->_strength !== null || $this->_lendableOnly);
	}
	
	public function getTemplateParameters() {
		
		$playersCount = PlayersDataService::findPlayersCount($this->_websoccer, $this->_db, 
				$this->_firstName, $this->_lastName, $this->_club, $this->_position, $this->_strength, $this->_lendableOnly);
		
		// setup paginator
		$eps = $this->_websoccer->getConfig("entries_per_page");
		$paginator = new Paginator($playersCount, $eps, $this->_websoccer);
		$paginator->addParameter("block", "playerssearch-results");
		$paginator->addParameter("fname", $this->_firstName);
		$paginator->addParameter("lname", $this->_lastName);
		$paginator->addParameter("club", $this->_club);
		$paginator->addParameter("position", $this->_position);
		$paginator->addParameter("strength", $this->_strength);
		$paginator->addParameter("lendable", $this->_lendableOnly);
		
		// get players records
		if ($playersCount > 0) {
			$players = PlayersDataService::findPlayers($this->_websoccer, $this->_db,
						$this->_firstName, $this->_lastName, $this->_club, $this->_position, $this->_strength, $this->_lendableOnly,
						$paginator->getFirstIndex(), $eps);
		} else {
			$players = array();
		}
		
		return array("playersCount" => $playersCount, "players" => $players, "paginator" => $paginator);
	}
	
	
}

?>