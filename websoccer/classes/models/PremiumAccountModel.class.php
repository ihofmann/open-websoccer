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
 * Provides premium account transactions.
 */
class PremiumAccountModel implements IModel {
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

		$userId = $this->_websoccer->getUser()->id;
		$count = PremiumDataService::countAccountStatementsOfUser($this->_websoccer, $this->_db, $userId);
		$eps = $this->_websoccer->getConfig("entries_per_page");
		$paginator = new Paginator($count, $eps, $this->_websoccer);
		
		if ($count > 0) {
			$statements = PremiumDataService::getAccountStatementsOfUser($this->_websoccer, $this->_db, $userId, $paginator->getFirstIndex(), $eps);
		} else {
			$statements = array();
		}
		
		return array("statements" => $statements, "paginator" => $paginator,
				"payments" => PremiumDataService::getPaymentsOfUser($this->_websoccer, $this->_db, $userId, 5));
		
	}
	
}
?>