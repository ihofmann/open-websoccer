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
class TransferOffersModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	public function renderView() {
		return ($this->_websoccer->getConfig("transferoffers_enabled") == 1);
	}
	
	public function getTemplateParameters() {
		
		$clubId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		
		$offers = array();
		$count = DirectTransfersDataService::countReceivedOffers($this->_websoccer, $this->_db, $clubId);
		
		$eps = $this->_websoccer->getConfig("entries_per_page");
		$paginator = new Paginator($count, $eps, $this->_websoccer);
		
		if ($count > 0) {
			$offers = DirectTransfersDataService::getReceivedOffers($this->_websoccer, $this->_db, 
					$paginator->getFirstIndex(), $eps, $clubId);
		} else {
			$offers = array();
		}
		
		return array("offers" => $offers, "paginator" => $paginator);
	}
	
}

?>