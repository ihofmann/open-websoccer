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
 * Data for training page.
 */
class TrainingModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::renderView()
	 */
	public function renderView() {
		return TRUE;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {
		
		$teamId = $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db);

		// last training executions
		$lastExecution = TrainingDataService::getLatestTrainingExecutionTime($this->_websoccer, $this->_db, $teamId);
		$unitsCount = TrainingDataService::countRemainingTrainingUnits($this->_websoccer, $this->_db, $teamId);
		$paginator = null;
		$trainers = null;
		
		// has valid training unit?
		$training_unit = TrainingDataService::getValidTrainingUnit($this->_websoccer, $this->_db, $teamId);
		if (!isset($training_unit["id"])) {
			// get trainers
			$count = TrainingDataService::countTrainers($this->_websoccer, $this->_db);
			$eps = $this->_websoccer->getConfig("entries_per_page");
			$paginator = new Paginator($count, $eps, $this->_websoccer);
			
			if ($count > 0) {
				$trainers = TrainingDataService::getTrainers($this->_websoccer, $this->_db, $paginator->getFirstIndex(), $eps);
			}
		} else {
			$training_unit["trainer"] = TrainingDataService::getTrainerById($this->_websoccer, $this->_db, $training_unit["trainer_id"]);
		}
		
		// collect effects of executed training unit
		$trainingEffects = array();
		$contextParameters = $this->_websoccer->getContextParameters();
		if (isset($contextParameters["trainingEffects"])) {
			$trainingEffects = $contextParameters["trainingEffects"];
		}
		
		return array("unitsCount" => $unitsCount, "lastExecution" => $lastExecution, "training_unit" => $training_unit, 
				"trainers" => $trainers, "paginator" => $paginator, "trainingEffects" => $trainingEffects);
	}
	
}

?>