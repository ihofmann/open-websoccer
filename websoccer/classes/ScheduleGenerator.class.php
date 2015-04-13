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
define('DUMMY_TEAM_ID', -1);

/**
 * Generates round robin schedules.
 */
class ScheduleGenerator {
	
	/**
	 * Generates a randomized tournament schedule. Odd number of teams supported.
	 * 
	 * @param array $teamIds array of team IDs
	 * @return array Array with key=matchday number (starting with 1), value= array of matches (each match is array with HomeId, GuestId).
	 */
	public static function createRoundRobinSchedule($teamIds) {
		
		// randomize
		shuffle($teamIds);
		
		$noOfTeams = count($teamIds);
		
		// support odd number of teams by adding a dummy team which will be filtered later
		if ($noOfTeams % 2 !== 0) {
			$teamIds[] = DUMMY_TEAM_ID;
			$noOfTeams++;
		}
		
		$noOfMatchDays = $noOfTeams - 1;
		
		// sort teams for every match day
		$sortedMatchdays = array();
		
		// fill first match day in the order of given teams
		foreach ($teamIds as $teamId) {
			$sortedMatchdays[1][] = $teamId;
		}
		
		for ($matchdayNo = 2; $matchdayNo <= $noOfMatchDays; $matchdayNo++) {
			
			// first half of row
			$rowCenterWithoutFixedEnd = $noOfTeams / 2 - 1;
			for ($teamIndex = 0; $teamIndex < $rowCenterWithoutFixedEnd; $teamIndex++) {
				$targetIndex = $teamIndex + $noOfTeams / 2;
				$sortedMatchdays[$matchdayNo][] = $sortedMatchdays[$matchdayNo - 1][$targetIndex];
			}
			
			// second half
			for ($teamIndex = $rowCenterWithoutFixedEnd; $teamIndex < $noOfTeams - 1; $teamIndex++) {
				$targetIndex = 0 + $teamIndex - $rowCenterWithoutFixedEnd;
				$sortedMatchdays[$matchdayNo][] = $sortedMatchdays[$matchdayNo - 1][$targetIndex];
			}
			
			// append fixed end
			$sortedMatchdays[$matchdayNo][] = $teamIds[count($teamIds) - 1];
		}
		
		// create combinations
		$schedule = array();
		$matchesNo = $noOfTeams / 2;
		for ($matchDayNo = 1; $matchDayNo <= $noOfMatchDays; $matchDayNo++) {
			
			$matches = array();
			for ($teamNo = 1; $teamNo <= $matchesNo; $teamNo++) {
				
				$homeTeam = $sortedMatchdays[$matchDayNo][$teamNo - 1];
				$guestTeam = $sortedMatchdays[$matchDayNo][count($teamIds) - $teamNo];
				
				if ($homeTeam == DUMMY_TEAM_ID || $guestTeam == DUMMY_TEAM_ID) {
					continue;
				}
				
				// alternate the first match (which contains the fixed end)
				if ($teamNo === 1 && $matchDayNo % 2 == 0) {
					$swapTemp = $homeTeam;
					$homeTeam = $guestTeam;
					$guestTeam = $swapTemp;
				}
				
				$match = array($homeTeam, $guestTeam);
				$matches[] = $match;
			}
			
			$schedule[$matchDayNo] = $matches;
		}

		return $schedule;
	}
}

?>