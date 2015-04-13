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
 * Provides current absences information for user.
 */
class AbsenceModel implements IModel {
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
		
		$absence = AbsencesDataService::getCurrentAbsenceOfUser($this->_websoccer, $this->_db, 
				$this->_websoccer->getUser()->id);
		
		$deputyName = "";
		if ($absence && $absence['deputy_id']) {
			$result = $this->_db->querySelect('nick', $this->_websoccer->getConfig('db_prefix') . '_user',
					'id = %d', $absence['deputy_id']);
			$deputy = $result->fetch_array();
			$result->free();
			$deputyName = $deputy['nick'];
		}
		
		return array('currentAbsence' => $absence, 'deputyName' => $deputyName);
	}
	
}

?>