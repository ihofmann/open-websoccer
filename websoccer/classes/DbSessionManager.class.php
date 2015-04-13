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
 * Provides data base backed user sessions.
 * 
 * @author Ingo Hofmann
 */
class DbSessionManager {

	private $_db;
	private $_websoccer;

	/**
	 * Creates new session manager.
	 * 
	 * @param DbConnection $db Database connection
	 * @param WebSoccer $websoccer Application context.
	 */
	function __construct(DbConnection $db, WebSoccer $websoccer) {
		$this->_db = $db;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * Unrelevant for DB based session management.
	 * Just override file based management.
	 * 
	 * @param string $save_path
	 * @param string $session_name
	 * @return boolean TRUE
	 */
	public function open($save_path, $session_name) {
		return true;
	}
	
	/**
	 * Irrelevant for DB based session management.
	 * Just override file based management.
	 * 
	 * @return boolean TRUE
	 */
	public function close() {
		return true;
	}
	
	/**
	 * Destroy user session.
	 * 
	 * @param string $sessionId session ID.
	 * @return boolean TRUE
	 */
	public function destroy($sessionId) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_session';
		$whereCondition = 'session_id = \'%s\'';
		
		$this->_db->queryDelete($fromTable, $whereCondition, $sessionId);
		return true;
	}
	
	/**
	 * Get data from session.
	 * 
	 * @param string $sessionId session id
	 * @return string data
	 */
	public function read($sessionId) {
		$columns = 'expires, session_data';
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_session';
		$whereCondition = 'session_id = \'%s\'';
		
		$data = NULL;
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $sessionId);
		if ($result->num_rows > 0) {
			
			$row = $result->fetch_array();
			
			// check whether expired
			if ($row['expires'] < $this->_websoccer->getNowAsTimestamp()) {
				$this->destroy($sessionId);
			} else {
				$data = $row['session_data'];
				if ($data == null) {
					$data = '';
				}
			}
			
		}
		
		$result->free();
		return $data;		
	}
	
	/**
	 * Write data to session.
	 * 
	 * @param string $sessionId session ID
	 * @param string $data data
	 */
	public function write($sessionId, $data) {
		$lifetime = (int) $this->_websoccer->getConfig('session_lifetime');
		$expiry = $this->_websoccer->getNowAsTimestamp() + $lifetime;
		
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_session';
		$columns['session_data'] = $data;
		$columns['expires'] = $expiry;
		
		// either insert or update
		if ($this->read($sessionId) !== NULL) {
			$whereCondition = 'session_id = \'%s\'';
			
			$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $sessionId);
		} else {
			$columns['session_id'] = $sessionId;
			$this->_db->queryInsert($columns, $fromTable);
		}
	}
	
	/**
	 * Garbage collection.
	 * 
	 * @return boolean TRUE
	 */
	public function gc() {
		$this->_deleteExpiredSessions();
		return true;
	}
	
	private function _deleteExpiredSessions() {
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_session';
		$whereCondition = 'expires < %d';
		
		$this->_db->queryDelete($fromTable, $whereCondition, $this->_websoccer->getNowAsTimestamp());
	}
}
?>