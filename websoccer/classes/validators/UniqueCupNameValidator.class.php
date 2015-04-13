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
 * Checks whether there is already a cup with the specified name.
 * 
 * @author Ingo Hofmann
 */
class UniqueCupNameValidator implements IValidator {
	private $_i18n;
	private $_websoccer;
	private $_value;
	
	/**
	 * @param I18n $i18n i18n instance.
	 * @param WebSoccer $websoccer Websoccer instance.
	 * @param mixed $value value to be validated.
	 */
	public function __construct($i18n, $websoccer, $value) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_value = $value;
	}
	
	/**
	 * @see IValidator::isValid()
	 */
	public function isValid() {
		
		$db = DbConnection::getInstance();
		
		// any cup with same name (but different ID)?
		$result = $db->querySelect('id', $this->_websoccer->getConfig('db_prefix') . '_cup', 
				'name = \'%s\'', $this->_value, 1);
		$cups = $result->fetch_array();
		$result->free();
		
		if (isset($cups['id']) && (!isset($_POST['id']) || $_POST['id'] !== $cups['id'])) {
			return FALSE;
		}
		
		// any match using the name for cup name?
		$result = $db->querySelect('COUNT(*) AS hits', $this->_websoccer->getConfig('db_prefix') . '_spiel',
				'pokalname = \'%s\'', $this->_value);
		$matches = $result->fetch_array();
		$result->free();
		
		if ($matches['hits'] && !isset($_POST['id'])) {
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * @see IValidator::getMessage()
	 */
	public function getMessage() {
		return $this->_i18n->getMessage('validation_error_uniquecupname');
	}
	
}

?>