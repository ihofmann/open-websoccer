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
 * Data service for random events.
 */
class RandomEventsDataService {
	
	/**
	 * Checks whether a new random event is due for the specified user and executes it.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user.
	 */
	public static function createEventIfRequired(WebSoccer $websoccer, DbConnection $db, $userId) {
		
		// is feature enabled?
		$eventsInterval = (int) $websoccer->getConfig('randomevents_interval_days');
		if ($eventsInterval < 1) {
			return;
		}
		
		// user must manage at least one team
		$result = $db->querySelect('id', $websoccer->getConfig('db_prefix') . '_verein', 'user_id = %d AND status = \'1\'', $userId);
		$clubIds = array();
		while ($club = $result->fetch_array()) {
			$clubIds[] = $club['id'];
		}
		$result->free();
		if (!count($clubIds)) {
			return;
		}
		
		// select radomly one of the user's teams
		$clubId = $clubIds[array_rand($clubIds)];
		
		// do not create an event within first 24 hours of registration
		$now = $websoccer->getNowAsTimestamp();
		
		$result = $db->querySelect('datum_anmeldung', $websoccer->getConfig('db_prefix') . '_user',
				'id = %d', $userId, 1);
		$user = $result->fetch_array();
		$result->free();
		if ($user['datum_anmeldung'] >= ($now - 24 * 3600)) {
			return;
		}
		
		// is a new event due? check occurance of latest event for user
		$result = $db->querySelect('occurrence_date', $websoccer->getConfig('db_prefix') . '_randomevent_occurrence',
				'user_id = %d ORDER BY occurrence_date DESC', $userId, 1);
		$latestEvent = $result->fetch_array();
		$result->free();
		if ($latestEvent && $latestEvent['occurrence_date'] >= ($now - 24 * 3600 * $eventsInterval)) {
			return;
		}
		
		// create and execute an event occurence
		self::_createAndExecuteEvent($websoccer, $db, $userId, $clubId);
		
		// delete old occurences. Delete those which are older than 10 intervals. 
		// In general, only the latest 10 occurences should remain.
		if ($latestEvent) {
			$deleteBoundary = $now - 24 * 3600 * 10 * $eventsInterval;
			$db->queryDelete($websoccer->getConfig('db_prefix') . '_randomevent_occurrence', 
					'user_id = %d AND occurrence_date < %d', array($userId, $deleteBoundary));
		}
	}

	private static function _createAndExecuteEvent(WebSoccer $websoccer, DbConnection $db, $userId, $clubId) {
		
		// get events which have not occured lately for the same user.
		// Since admin might have created a lot of events, we pick any 100 random events (ignoring weights here).
		$result = $db->querySelect('*', $websoccer->getConfig('db_prefix') . '_randomevent', 
				'weight > 0 AND id NOT IN (SELECT event_id FROM ' . $websoccer->getConfig('db_prefix') . '_randomevent_occurrence WHERE user_id = %d) ORDER BY RAND()', $userId,
				100);
		$events = array();
		while ($event = $result->fetch_array()) {
			// add "weight" times in order to increase probability
			for ($i = 1; $i <= $event['weight']; $i++) {
				$events[] = $event;
			}
		}
		$result->free();
		
		if (!count($events)) {
			return;
		}
		
		// select and execute event
		$randomEvent = $events[array_rand($events)];
		self::_executeEvent($websoccer, $db, $userId, $clubId, $randomEvent);
		
		// create occurence log
		$db->queryInsert(array(
				'user_id' => $userId,
				'team_id' => $clubId,
				'event_id' => $randomEvent['id'],
				'occurrence_date' => $websoccer->getNowAsTimestamp()
				), 
				$websoccer->getConfig('db_prefix') . '_randomevent_occurrence');
		
	}
	
	private static function _executeEvent(WebSoccer $websoccer, DbConnection $db, $userId, $clubId, $event) {
		
		$notificationType = 'randomevent';
		$subject = $event['message'];
		
		// debit or credit money
		if ($event['effect'] == 'money') {
			$amount = $event['effect_money_amount'];
			$sender = $websoccer->getConfig('projectname');
			
			if ($amount > 0) {
				BankAccountDataService::creditAmount($websoccer, $db, $clubId, $amount, $subject, $sender);
			} else {
				BankAccountDataService::debitAmount($websoccer, $db, $clubId, $amount * (0-1), $subject, $sender);
			}
			
			// notification
			NotificationsDataService::createNotification($websoccer, $db, $userId, $subject, null, 
				$notificationType, 'finances', null, $clubId);
			
			// execute on random player
		} else {
			
			// select random player from team
			$result = $db->querySelect('id, vorname, nachname, kunstname, w_frische, w_kondition, w_zufriedenheit', 
					$websoccer->getConfig('db_prefix') . '_spieler',
					'verein_id = %d AND gesperrt = 0 AND verletzt = 0 AND status = \'1\' ORDER BY RAND()', $clubId, 1);
			$player = $result->fetch_array();
			$result->free();
			if (!$player) {
				return;
			}
			
			// execute (get update column)
			switch ($event['effect']) {
				case 'player_injured':
					$columns = array('verletzt' => $event['effect_blocked_matches']);
					break;
					
				case 'player_blocked':
					$columns = array('gesperrt' => $event['effect_blocked_matches']);
					break;
					
				case 'player_happiness':
					$columns = array('w_zufriedenheit' => max(1, min(100, $player['w_zufriedenheit'] + $event['effect_skillchange'])));
					break;
					
				case 'player_fitness':
					$columns = array('w_frische' => max(1, min(100, $player['w_frische'] + $event['effect_skillchange'])));
					break;
					
				case 'player_stamina':
					$columns = array('w_kondition' => max(1, min(100, $player['w_kondition'] + $event['effect_skillchange'])));
					break;
			}
			
			// update player
			if (!isset($columns)) {
				return;
			}
			$db->queryUpdate($columns, $websoccer->getConfig('db_prefix') . '_spieler', 'id = %d', $player['id']);
			
			// create notification
			$playerName = (strlen($player['kunstname'])) ? $player['kunstname'] : $player['vorname'] . ' ' . $player['nachname'];
			NotificationsDataService::createNotification($websoccer, $db, $userId, $subject, array('playername' => $playerName), 
				$notificationType, 'player', 'id=' . $player['id'], $clubId);
		}
		
	}
}
?>