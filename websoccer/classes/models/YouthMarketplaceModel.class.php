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
 * Provides all transferable youth players.
 */
class YouthMarketplaceModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return $this->_websoccer->getConfig("youth_enabled");
	}
	
	public function getTemplateParameters() {
		
		$positionFilter = $this->_websoccer->getRequestParameter("position");
		
		$count = YouthPlayersDataService::countTransferableYouthPlayers($this->_websoccer, $this->_db, $positionFilter);
		$eps = $this->_websoccer->getConfig("entries_per_page");
		$paginator = new Paginator($count, $eps, $this->_websoccer);
		
		if ($positionFilter != null) {
			$paginator->addParameter("position", $positionFilter);
		}
		
		$players = YouthPlayersDataService::getTransferableYouthPlayers($this->_websoccer, $this->_db, $positionFilter, $paginator->getFirstIndex(), $eps);
		
		return array("players" => $players, "paginator" => $paginator);
	}
	
}

?>