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
 * Move players who do not have any team to the transfer market.
 * 
 * @author Ingo Hofmann
 */
class AddPlayerWithoutTeamToTransfermarketJob extends AbstractJob {
	
	/**
	 * @see AbstractJob::execute()
	 */
	function execute() {
		TransfermarketDataService::movePlayersWithoutTeamToTransfermarket($this->_websoccer, $this->_db);
	}
}

?>
