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
 * Provides next match of current user's national team.
 */
class NationalNextMatchModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_teamId;
	
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
		if (!$this->_websoccer->getConfig('nationalteams_enabled')) {
			return FALSE;
		}
		
		// get team info
		$this->_teamId = NationalteamsDataService::getNationalTeamManagedByCurrentUser($this->_websoccer, $this->_db);
		if (!$this->_teamId) {
			return FALSE;
		}
		
		$matchesCount = NationalteamsDataService::countNextMatches($this->_websoccer, $this->_db, $this->_teamId);
		if (!$matchesCount) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$matches = NationalteamsDataService::getNextMatches($this->_websoccer, $this->_db, $this->_teamId, 0, 1);
		
		return array('match' => $matches[0]);
	}
	
}

?>