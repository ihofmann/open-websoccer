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
 * Handles user passwords.
 * 
 * @author Ingo Hofmann
 */
class UserPasswordConverter implements IConverter {
	private $_i18n;
	private $_websoccer;
	
	public function __construct($i18n, $websoccer) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * @see IConverter::toHtml()
	 */
	public function toHtml($value) {
		return $this->toText($value);
	}
	
	/**
	 * @see IConverter::toText()
	 */
	public function toText($value) {
		return $value;
	}
	
	/**
	 * @see IConverter::toDbValue()
	 */
	public function toDbValue($value) {

		// use salt only when updating
		if (isset($_POST['id']) && $_POST['id']) {
			$db = DbConnection::getInstance();
			$columns = 'passwort, passwort_salt';
			$fromTable = $this->_websoccer->getConfig('db_prefix') .'_user';
			$whereCondition = 'id = %d';
			$result = $db->querySelect($columns, $fromTable, $whereCondition, $_POST['id'], 1);
			$user = $result->fetch_array();
			$result->free();
			
			if (strlen($value)) {
				$passwort = SecurityUtil::hashPassword($value, $user['passwort_salt']);
			} else {
				$passwort = $user['passwort'];
			}
		} else {
			$passwort = SecurityUtil::hashPassword($value, '');
		}
		
		return $passwort;
	}
	
}

?>