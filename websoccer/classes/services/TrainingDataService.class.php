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
 * Data service for training data
 */
class TrainingDataService {
	
	public static function countTrainers(WebSoccer $websoccer, DbConnection $db) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_trainer";
	
		// where
		$whereCondition = "1=1";
	
		// select
		$columns = "COUNT(*) AS hits";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition);
		$trainers = $result->fetch_array();
		$result->free();
	
		return $trainers["hits"];
	}
	
	public static function getTrainers(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_trainer";
	
		// where
		$whereCondition = "1=1 ORDER BY salary DESC";
	
		// select
		$columns = "*";
		
		$limit = $startIndex .",". $entries_per_page;
	
		$trainers = array();
		$result = $db->querySelect($columns, $fromTable, $whereCondition, null, $limit);
		while ($trainer = $result->fetch_array()) {
			$trainers[] = $trainer;
		}
		$result->free();
		
		return $trainers;
	}
	
	public static function getTrainerById(WebSoccer $websoccer, DbConnection $db, $trainerId) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_trainer";
	
		// where
		$whereCondition = "id = %d";
	
		// select
		$columns = "*";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $trainerId);
		$trainer = $result->fetch_array();
		$result->free();
	
		return $trainer;
	}
	
	public static function countRemainingTrainingUnits(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$columns = "COUNT(*) AS hits";
		$fromTable = $websoccer->getConfig("db_prefix") . "_training_unit";
		$whereCondition = "team_id = %d AND date_executed = 0 OR date_executed IS NULL";
		$parameters = $teamId;
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$units = $result->fetch_array();
		$result->free();
		
		return $units["hits"];
	}
	
	public static function getLatestTrainingExecutionTime(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$columns = "date_executed";
		$fromTable = $websoccer->getConfig("db_prefix") . "_training_unit";
		$whereCondition = "team_id = %d AND date_executed > 0 ORDER BY date_executed DESC";
		$parameters = $teamId;
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$unit = $result->fetch_array();
		$result->free();
		
		if (isset($unit["date_executed"])) {
			return $unit["date_executed"];
		} else {
			return 0;
		}
	}
	
	public static function getValidTrainingUnit(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$columns = "id,trainer_id";
		$fromTable = $websoccer->getConfig("db_prefix") . "_training_unit";
		$whereCondition = "team_id = %d AND date_executed = 0 OR date_executed IS NULL ORDER BY id ASC";
		$parameters = $teamId;
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$unit = $result->fetch_array();
		$result->free();
		
		return $unit;
	}
	
	public static function getTrainingUnitById(WebSoccer $websoccer, DbConnection $db, $teamId, $unitId) {
		$columns = "*";
		$fromTable = $websoccer->getConfig("db_prefix") . "_training_unit";
		$whereCondition = "id = %d AND team_id = %d";
		$parameters = array($unitId, $teamId);
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$unit = $result->fetch_array();
		$result->free();
	
		return $unit;
	}
	
}
?>