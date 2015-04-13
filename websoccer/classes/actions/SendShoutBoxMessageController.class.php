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
 * Saves a submitted shoutbox message.
 *
 */
class SendShoutBoxMessageController implements IActionController {
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
		
		$userId = $this->_websoccer->getUser()->id;
		$message = $parameters['msgtext'];
		$matchId = $parameters['id'];
		$date = $this->_websoccer->getNowAsTimestamp();
		
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_shoutmessage';
		$this->_db->queryInsert(array(
				'user_id' => $userId,
				'message' => $message,
				'created_date' => $date,
				'match_id' => $matchId
				), $fromTable);
		
		// delete old messages
		if (!isset($_SESSION['msgdeleted'])) {
			// delete messages which are older than 14 days
			$threshold = $date - 24 * 3600 * 14;
			$this->_db->queryDelete($fromTable, "created_date < %d", $threshold);
			$_SESSION['msgdeleted'] = 1;
		}
		
		return null;
	}
	
}

?>