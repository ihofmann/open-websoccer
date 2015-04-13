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
 * Observer which creates match report texts for each event of a youth match
 * 
 * @author Ingo Hofmann
 */
class YouthMatchReportSimulationObserver implements ISimulationObserver {
	
	private $_websoccer;
	private $_db;
	
	/**
	 * 
	 * @param WebSoccer $websoccer request context.
	 * @param DbConnection $db database connection.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db) {
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * @see ISimulationObserver::onGoal()
	 */
	public function onGoal(SimulationMatch $match, SimulationPlayer $scorer, SimulationPlayer $goaly) {
		
		YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute, 
			'ymreport_goal', array('scorer' => $scorer->name), $scorer->team->id == $match->homeTeam->id);
	}
	
	/**
	 * @see ISimulationObserver::onShootFailure()
	 */
	public function onShootFailure(SimulationMatch $match, SimulationPlayer $scorer, SimulationPlayer $goaly) {
		
		YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
			'ymreport_attempt', array('scorer' => $scorer->name), $scorer->team->id == $match->homeTeam->id);
	}
	
	/**
	 * @see ISimulationObserver::onAfterTackle()
	 */
	public function onAfterTackle(SimulationMatch $match, SimulationPlayer $winner, SimulationPlayer $looser) {
		
		YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
			'ymreport_tackle', array('winner' => $winner->name, 'loser' => $looser->name), $winner->team->id == $match->homeTeam->id);
		
	}
	
	/**
	 * Empty implementation since successful passes will not be commented.
	 * 
	 * @see ISimulationObserver::onBallPassSuccess()
	 */
	public function onBallPassSuccess(SimulationMatch $match, SimulationPlayer $player) {
	}
	
	/**
	 * Empty implementation. We do not track it for youth players.
	 * 
	 * @see ISimulationObserver::onBallPassFailure()
	 */
	public function onBallPassFailure(SimulationMatch $match, SimulationPlayer $player) {
	}
	
	/**
	 * @see ISimulationObserver::onInjury()
	 */
	public function onInjury(SimulationMatch $match, SimulationPlayer $player, $numberOfMatches) {
		YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
			'ymreport_injury', array('player' => $player->name), $player->team->id == $match->homeTeam->id);
	}
	
	/**
	 * @see ISimulationObserver::onYellowCard()
	 */
	public function onYellowCard(SimulationMatch $match, SimulationPlayer $player) {
		if ($player->yellowCards > 1) {
			YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
				'ymreport_card_yellowred', array('player' => $player->name), $player->team->id == $match->homeTeam->id);
		} else {
			YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
				'ymreport_card_yellow', array('player' => $player->name), $player->team->id == $match->homeTeam->id);
		}
		
		
	}
	
	/**
	 * @see ISimulationObserver::onRedCard()
	 */
	public function onRedCard(SimulationMatch $match, SimulationPlayer $player, $matchesBlocked) {
		YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
				'ymreport_card_red', array('player' => $player->name), $player->team->id == $match->homeTeam->id);
	}
	
	/**
	 * @see ISimulationObserver::onPenaltyShoot()
	 */
	public function onPenaltyShoot(SimulationMatch $match, SimulationPlayer $player, SimulationPlayer $goaly, $successful) {
	
		if ($successful) {
			YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
				'ymreport_penalty_success', array('player' => $player->name), $player->team->id == $match->homeTeam->id);
		} else {
			YouthMatchesDataService::createMatchReportItem($this->_websoccer, $this->_db, $match->id, $match->minute,
				'ymreport_penalty_failure', array('player' => $player->name), $player->team->id == $match->homeTeam->id);
		}
	}
	
	/**
	 * Empty implementation. We do not track it for youth players.
	 * 
	 * @see ISimulationObserver::onCorner()
	 */
	public function onCorner(SimulationMatch $match, SimulationPlayer $concededByPlayer, SimulationPlayer $targetPlayer) {
	}
	
	/**
	 * Creates a match report item if free kick was successful.
	 * 
	 * @see ISimulationObserver::onFreeKick()
	 */
	public function onFreeKick(SimulationMatch $match, SimulationPlayer $player, SimulationPlayer $goaly, $successful) {
		if ($successful) {
			$this->onGoal($match, $player, $goaly);
		}
	}
	
}
?>