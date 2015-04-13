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
 * Data service for creating and reading action log records.
 */
class ActionLogDataService {
	
	
	/**
	 * Provides latest action logs of specified user.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection.
	 * @param int $userId ID of user.
	 * @param int $limit Maximum number of records to fetch.
	 * @return array List of log entries or empty array.
	 */
	public static function getActionLogsOfUser(WebSoccer $websoccer, DbConnection $db, $userId, $limit = 10) {
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_useractionlog AS L';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_user AS U ON U.id = L.user_id';
		
		$columns = array(
				'L.id' => 'log_id',
				'L.action_id' => 'action_id',
				'L.user_id' => 'user_id',
				'L.created_date' => 'created_date',
				'U.nick' => 'user_name'
				);
		
		$result = $db->querySelect($columns, $fromTable, 
				'L.user_id = %d ORDER BY L.created_date DESC', $userId, $limit);
		
		$logs = array();
		while ($log = $result->fetch_array()) {
			$logs[] = $log;
		}
		$result->free();
		
		return $logs;
	}
	
	/**
	 * Provides latest action logs in game.
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection.
	 * @param int $limit Maximum number of records to fetch.
	 * @return array List of log entries or empty array.
	 */
	public static function getLatestActionLogs(WebSoccer $websoccer, DbConnection $db, $limit = 10) {
	
		$fromTable = $websoccer->getConfig('db_prefix') . '_useractionlog AS L';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_user AS U ON U.id = L.user_id';
	
		$columns = array(
				'L.id' => 'log_id',
				'L.action_id' => 'action_id',
				'L.user_id' => 'user_id',
				'L.created_date' => 'created_date',
				'U.nick' => 'user_name'
		);
	
		$result = $db->querySelect($columns, $fromTable,
				'1 ORDER BY L.id DESC', null, $limit);
	
		$logs = array();
		while ($log = $result->fetch_array()) {
			$logs[] = $log;
		}
		$result->free();
	
		return $logs;
	}
	
	/**
	 * Creates a new action log for the specified user and deletes old ones.
	 * If there is already a recent log for the same action, it will onl update the timestamp.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @param string $actionId Action name.
	 */
	public static function createOrUpdateActionLog(WebSoccer $websoccer, DbConnection $db, $userId, $actionId) {
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_useractionlog';
		
		// delete old entries of user (entries which are older than 20 days)
		$deleteTimeThreshold = $websoccer->getNowAsTimestamp() - 24 * 3600 * 20;
		$db->queryDelete($fromTable, 'user_id = %d AND created_date < %d', array($userId, $deleteTimeThreshold));
		
		// check if action has been triggered within the last X minutes. If so, just update timestamp rather than filling DB unnecessary.
		$timeThreshold = $websoccer->getNowAsTimestamp() - 30 * 60;
		$result = $db->querySelect('id', $fromTable, 'user_id = %d AND action_id = \'%s\' AND created_date >= %d ORDER BY created_date DESC', 
				array($userId, $actionId, $timeThreshold), 1);
		$lastLog = $result->fetch_array();
		$result->free();
		
		// update last log
		if ($lastLog) {
			
			$db->queryUpdate(array('created_date' => $websoccer->getNowAsTimestamp()), $fromTable, 
					'id = %d', $lastLog['id']);
			
		// create new log
		} else {
			
			$db->queryInsert(array(
					'user_id' => $userId,
					'action_id' => $actionId,
					'created_date' => $websoccer->getNowAsTimestamp()
					), $fromTable);
			
		}
	}

}
?>