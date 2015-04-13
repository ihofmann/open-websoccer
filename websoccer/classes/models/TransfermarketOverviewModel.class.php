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
class TransfermarketOverviewModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return ($this->_websoccer->getConfig("transfermarket_enabled") == 1);
	}
	
	public function getTemplateParameters() {
		
		$teamId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage("feature_requires_team"));
		}
		
		$positionInput = $this->_websoccer->getRequestParameter("position");
		$positionFilter = null;
		if ($positionInput == "goaly") {
			$positionFilter = "Torwart";
		} else if ($positionInput == "defense") {
			$positionFilter = "Abwehr";
		} else if ($positionInput == "midfield") {
			$positionFilter = "Mittelfeld";
		} else if ($positionInput == "striker") {
			$positionFilter = "Sturm";
		}
		
		$count = PlayersDataService::countPlayersOnTransferList($this->_websoccer, $this->_db, $positionFilter);
		$eps = $this->_websoccer->getConfig("entries_per_page");
		$paginator = new Paginator($count, $eps, $this->_websoccer);
		if ($positionFilter != null) {
			$paginator->addParameter("position", $positionInput);
		}
		
		if ($count > 0) {
			$players = PlayersDataService::getPlayersOnTransferList($this->_websoccer, $this->_db, $paginator->getFirstIndex(), $eps, $positionFilter);
		} else {
			$players = array();
		}
		
		return array("transferplayers" => $players, "playerscount" => $count, "paginator" => $paginator);
	}
	
}

?>