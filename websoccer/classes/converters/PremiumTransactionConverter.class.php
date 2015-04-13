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
 * Alters the users's premium credit.
 * 
 * @author Ingo Hofmann
 */
class PremiumTransactionConverter implements IConverter {
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

		$amount = (int) $value;
		
		if (isset($_POST['user_id']) && $_POST['user_id']) {
			
			// get current user budget
			$db = DbConnection::getInstance();
			$columns = 'premium_balance';
			$fromTable = $this->_websoccer->getConfig('db_prefix') .'_user';
			$whereCondition = 'id = %d';
			$result = $db->querySelect($columns, $fromTable, $whereCondition, $_POST['user_id'], 1);
			$user = $result->fetch_array();
			$result->free();
			
			// update budget in DB
			$budget = $user['premium_balance'] + $amount;
			$updatecolumns = array('premium_balance' => $budget);
			$db->queryUpdate($updatecolumns, $fromTable, $whereCondition, $_POST['user_id']);
		}
		
		return $amount;
	}
	
}

?>