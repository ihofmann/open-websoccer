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
 * Checks if any stadium construction is due and accepts the work.
 * Accepting means that, depending on the builder's reliability, the
 * completion might be delayed or done.
 * So either postpone the deadline or update the stadium.
 * And let the user know about the result by sending a user
 * notification.
 * 
 * PLUS, checks due training camp bookings.
 * 
 * @author Ingo Hofmann
 */
class AcceptStadiumConstructionWorkJob extends AbstractJob {
	
	/**
	 * @see AbstractJob::execute()
	 */
	function execute() {
		$this->checkStadiumConstructions();
		
		$this->checkTrainingCamps();
	}
	
	private function checkStadiumConstructions() {
		$constructions = StadiumsDataService::getDueConstructionOrders($this->_websoccer, $this->_db);
		$newDeadline = $this->_websoccer->getNowAsTimestamp() + $this->_websoccer->getConfig('stadium_construction_delay') * 24 * 3600;
		
		foreach ($constructions as $construction) {
				
			// is actually completed?
			$pStatus = array();
			$pStatus['completed'] = $construction['builder_reliability'];
			$pStatus['notcompleted'] = 100 - $pStatus['completed'];
			$constructionResult = SimulationHelper::selectItemFromProbabilities($pStatus);
				
			// not completed: postpone deadline
			if ($constructionResult == 'notcompleted') {
					
				$this->_db->queryUpdate(array('deadline' => $newDeadline), $this->_websoccer->getConfig('db_prefix') . '_stadium_construction',
						'id = %d', $construction['id']);
					
				// send notification
				if ($construction['user_id']) {
					NotificationsDataService::createNotification($this->_websoccer, $this->_db, $construction['user_id'],
					'stadium_construction_notification_delay', null, 'stadium_construction', 'stadium');
				}
					
				// completed
			} else {
					
				// update stadium
				$stadium = StadiumsDataService::getStadiumByTeamId($this->_websoccer, $this->_db, $construction['team_id']);
				$columns = array();
				$columns['p_steh'] = $stadium['places_stands'] + $construction['p_steh'];
				$columns['p_sitz'] = $stadium['places_seats'] + $construction['p_sitz'];
				$columns['p_haupt_steh'] = $stadium['places_stands_grand'] + $construction['p_haupt_steh'];
				$columns['p_haupt_sitz'] = $stadium['places_seats_grand'] + $construction['p_haupt_sitz'];
				$columns['p_vip'] = $stadium['places_vip'] + $construction['p_vip'];
				$this->_db->queryUpdate($columns, $this->_websoccer->getConfig('db_prefix') . '_stadion', 'id = %d',
						$stadium['stadium_id']);
					
				// delete order
				$this->_db->queryDelete($this->_websoccer->getConfig('db_prefix') . '_stadium_construction',
						'id = %d', $construction['id']);
					
				// send notification
				if ($construction['user_id']) {
					NotificationsDataService::createNotification($this->_websoccer, $this->_db, $construction['user_id'],
					'stadium_construction_notification_completed', null, 'stadium_construction', 'stadium');
				}
			}
		}
	}
	
	private function checkTrainingCamps() {
		
		$fromTable = $this->_websoccer->getConfig('db_prefix') . '_trainingslager_belegung AS B';
		$fromTable .= ' INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_trainingslager AS C ON C.id = B.lager_id';
		
		$columns['B.id'] = 'id';
		$columns['B.datum_start'] = 'date_start';
		$columns['B.datum_ende'] = 'date_end';
		$columns['B.verein_id'] = 'team_id';
		$columns['C.name'] = 'name';
		$columns['C.land'] = 'country';
		$columns['C.preis_spieler_tag'] = 'costs';
		$columns['C.p_staerke'] = 'effect_strength';
		$columns['C.p_technik'] = 'effect_strength_technique';
		$columns['C.p_kondition'] = 'effect_strength_stamina';
		$columns['C.p_frische'] = 'effect_strength_freshness';
		$columns['C.p_zufriedenheit'] = 'effect_strength_satisfaction';
		
		$whereCondition = 'B.datum_ende < %d';
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $this->_websoccer->getNowAsTimestamp());
		while ($booking = $result->fetch_array()) {
			TrainingcampsDataService::executeCamp($this->_websoccer, $this->_db, $booking['team_id'], $booking);
		}
		$result->free();
		
		
	}
}

?>
