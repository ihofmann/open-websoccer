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
 * Data service for user notifications.
 */
class NotificationsDataService {

	/**
	 * Creates a new unseen notification about any event which shall catch the user's attention.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of notification receiver.
	 * @param string $messageKey key of message to display. Can contain place holders in form of {myplaceholder}.
	 * @param array $messageData values of placeholder as an assoc. array. Array keys are message place holders.
	 * @param string $type Optional. Type of notification as a string ID. Can be used for displaying icons or similar in the view layer.
	 * @param string $targetPageId Optional. ID of page to which a link shall be targetting.
	 * @param string $targetPageQueryString Optional. Query string to append to the target page link.
	 * @param int $teamId Optional. ID of team to which this notification is assigned.
	 */
	public static function createNotification(WebSoccer $websoccer, DbConnection $db, $userId, $messageKey, 
			$messageData = null, $type = null, $targetPageId = null, $targetPageQueryString = null, $teamId = null) {
		
		$columns = array(
				'user_id' => $userId,
				'eventdate' => $websoccer->getNowAsTimestamp(),
				'message_key' => $messageKey
				);
		
		if ($messageData != null) {
			$columns['message_data'] = json_encode($messageData);
		}
		
		if ($type != null) {
			$columns['eventtype'] = $type;
		}
		
		if ($targetPageId != null) {
			$columns['target_pageid'] = $targetPageId;
		}
		
		if ($targetPageQueryString != null) {
			$columns['target_querystr'] = $targetPageQueryString;
		}
		
		if ($teamId != null) {
			$columns['team_id'] = $teamId;
		}
		
		$db->queryInsert($columns, $websoccer->getConfig('db_prefix') . '_notification');
	}
	
	/**
	 * Counts and returns number of unseen notifications of specified user.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 * @param int $teamId ID of user's team.
	 * @return int number of unseen notifications.
	 */
	public static function countUnseenNotifications(WebSoccer $websoccer, DbConnection $db, $userId, $teamId) {
		
		$result = $db->querySelect('COUNT(*) AS hits', $websoccer->getConfig('db_prefix') . '_notification', 
				'user_id = %d AND seen = \'0\' AND (team_id = %d OR team_id IS NULL)', array($userId, $teamId));
		$rows = $result->fetch_array();
		$result->free();
		
		if ($rows) {
			return $rows['hits'];
		}
		
		return 0;
	}
	
	/**
	 * Retrieves the latest notifications for the specified user.
	 * 
	 * @param WebSoccer $websoccer Application contex
	 * @param DbConnection $db DB connection
	 * @param I18n $i18n I18n context.
	 * @param int $userId ID of user
	 * @param int $teamId ID of user's currently selected team
	 * @param int $limit maximum number of notifications to return.
	 * @return array Array of assoc. arrays which represent a notification. A notification has keys id, eventdate, eventtype, seen, message, link
	 */
	public static function getLatestNotifications(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $userId, $teamId, $limit) {
		
		$result = $db->querySelect('*', $websoccer->getConfig('db_prefix') . '_notification', 
				'user_id = %d AND (team_id = %d OR team_id IS NULL) ORDER BY eventdate DESC', array($userId, $teamId), $limit);
		
		$notifications = array();
		while ($row = $result->fetch_array()) {
			$notification = array(
					'id' => $row['id'],
					'eventdate' => $row['eventdate'],
					'eventtype' => $row['eventtype'],
					'seen' => $row['seen']
					);
			
			// prepare message
			if ($i18n->hasMessage($row['message_key'])) {
				$message = $i18n->getMessage($row['message_key']);
			} else {
				$message = $row['message_key'];
			}
			
			// replace place holders
			if (strlen($row['message_data'])) {
				$messageData = json_decode($row['message_data'], true);
				
				if ($messageData) {
					foreach ($messageData as $placeholderName => $placeholderValue) {
						$message = str_replace('{' . $placeholderName . '}', 
								htmlspecialchars($placeholderValue, ENT_COMPAT, 'UTF-8'), $message);
					}
				}
			}
			
			$notification['message'] = $message;
			
			// add target link
			$link = '';
			if ($row['target_pageid']) {
				if ($row['target_querystr']) {
					$link = $websoccer->getInternalUrl($row['target_pageid'], $row['target_querystr']);
				} else {
					$link = $websoccer->getInternalUrl($row['target_pageid']);
				}
			}
			
			$notification['link'] = $link;
			
			$notifications[] = $notification;
		}
		return $notifications;
	}
}
?>