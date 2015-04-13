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
 * Removes a saved formation template.
 */
class RemoveFormationTemplateController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		$teamId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);
		
		$dbTable = $this->_websoccer->getConfig('db_prefix') . '_aufstellung';
		
		// verify that it is own template
		$result = $this->_db->querySelect('verein_id', $dbTable, 'id = %d', $parameters['templateid']);
		$template = $result->fetch_array();
		$result->free();
		if (!$template || $template['verein_id'] != $teamId) {
			throw new Exception('illegal template ID');
		}
		
		$this->_db->queryDelete($dbTable, 'id = %d', $parameters['templateid']);
		return null;
	}
	
}

?>