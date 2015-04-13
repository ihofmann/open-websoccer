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
 * Simulator observers allow actions on match processing triggered by the simulator.
 * 
 * @see Simulator
 * @author Ingo Hofmann
 */
interface ISimulatorObserver {

	/**
	 * A valid substitution has been executed.
	 * 
	 * @param SimulationMatch $match simulated match.
	 * @param SimulationSubstitution $substitution
	 */
	public function onSubstitution(SimulationMatch $match, SimulationSubstitution $substitution);
	
	/**
	 * The match has ended.
	 * 
	 * @param SimulationMatch $match simulated match.
	 */
	public function onMatchCompleted(SimulationMatch $match);
	
	/**
	 * The match is about to start.
	 * 
	 * @param SimulationMatch $match simulated match.
	 */
	public function onBeforeMatchStarts(SimulationMatch $match);
	
}
?>