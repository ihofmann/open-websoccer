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
 * Data service for user messages.
 */
class MessagesDataService {

	
	public static function getInboxMessages(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
		
		$whereCondition = "L.empfaenger_id = %d AND L.typ = 'eingang' ORDER BY L.datum DESC";
		$parameters = $websoccer->getUser()->id;
		
		return self::getMessagesByCondition($websoccer, $db, $startIndex, $entries_per_page, $whereCondition, $parameters);
	}
	
	public static function getOutboxMessages(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
	
		$whereCondition = "L.absender_id = %d AND L.typ = 'ausgang' ORDER BY L.datum DESC";
		$parameters = $websoccer->getUser()->id;
	
		return self::getMessagesByCondition($websoccer, $db, $startIndex, $entries_per_page, $whereCondition, $parameters);
	}
	
	public static function getMessageById(WebSoccer $websoccer, DbConnection $db, $id) {
		$whereCondition = "(L.empfaenger_id = %d OR L.absender_id = %d) AND L.id = %d";
		$userId = $websoccer->getUser()->id;
		$parameters = array($userId, $userId, $id);
		
		$messages = self::getMessagesByCondition($websoccer, $db, 0, 1, $whereCondition, $parameters);
		if (count($messages)) {
			return $messages[0];
		}
		
		return null;
	}
	
	public static function getLastMessageOfUserId(WebSoccer $websoccer, DbConnection $db, $userId) {
		$whereCondition = "L.absender_id = %d ORDER BY L.datum DESC";
		$userId = $websoccer->getUser()->id;
	
		$messages = self::getMessagesByCondition($websoccer, $db, 0, 1, $whereCondition, $userId);
		if (count($messages)) {
			return $messages[0];
		}
	
		return null;
	}
	
	private static function getMessagesByCondition(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page, $whereCondition, $parameters) {
		
		$columns["L.id"] = "message_id";
		$columns["L.betreff"] = "subject";
		$columns["L.nachricht"] = "content";
		$columns["L.datum"] = "date";
		$columns["L.gelesen"] = "seen";
		
		$columns["R.id"] = "recipient_id";
		$columns["R.nick"] = "recipient_name";
		
		$columns["S.id"] = "sender_id";
		$columns["S.nick"] = "sender_name";
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_briefe AS L";
		$fromTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_user AS R ON R.id = L.empfaenger_id";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_user AS S ON S.id = L.absender_id";
		
		$limit = $startIndex .",". $entries_per_page;
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $limit);
		
		$messages = array();
		while ($message = $result->fetch_array()) {
			$messages[] = $message;
		}
		$result->free();
		
		return $messages;
	}
	
	public static function countInboxMessages(WebSoccer $websoccer, DbConnection $db) {
		$userId = $websoccer->getUser()->id;
		
		$columns = "COUNT(*) AS hits";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_briefe AS L";
	
		$whereCondition = "L.empfaenger_id = %d AND typ = 'eingang'";
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $userId);
		$letters = $result->fetch_array();
		$result->free();
		
		if (isset($letters["hits"])) {
			return $letters["hits"];
		}
	
		return 0;
	}
	
	public static function countUnseenInboxMessages(WebSoccer $websoccer, DbConnection $db) {
		$userId = $websoccer->getUser()->id;
	
		$columns = "COUNT(*) AS hits";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_briefe AS L";
	
		$whereCondition = "L.empfaenger_id = %d AND typ = 'eingang' AND gelesen = '0'";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $userId);
		$letters = $result->fetch_array();
		$result->free();
	
		if (isset($letters["hits"])) {
			return $letters["hits"];
		}
	
		return 0;
	}
	
	public static function countOutboxMessages(WebSoccer $websoccer, DbConnection $db) {
		$userId = $websoccer->getUser()->id;
	
		$columns = "COUNT(*) AS hits";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_briefe AS L";
	
		$whereCondition = "L.absender_id = %d AND typ = 'ausgang'";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $userId);
		$letters = $result->fetch_array();
		$result->free();
	
		if (isset($letters["hits"])) {
			return $letters["hits"];
		}
	
		return 0;
	}
	
	
}
?>