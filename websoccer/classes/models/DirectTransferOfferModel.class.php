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
 * @author Ingo Hofmann
 */
class DirectTransferOfferModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;
	private $_player;
	
	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		
		$playerId = (int) $this->_websoccer->getRequestParameter("id");
		if ($playerId < 1) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$this->_player = PlayersDataService::getPlayerById($this->_websoccer, $this->_db, $playerId);
		
	}
	
	public function renderView() {
		// is feature enabled?
		if (!$this->_websoccer->getConfig("transferoffers_enabled")) {
			return FALSE;
		}
		
		// is player seallable and is playing in a team with a manager; and is not borrowed
		return (!$this->_player["player_unsellable"] && $this->_player["team_user_id"] > 0 
				&& $this->_player["team_user_id"] !== $this->_websoccer->getUser()->id
				&& !$this->_player["player_transfermarket"]
				&& $this->_player["lending_owner_id"] == 0);
	}
	
	public function getTemplateParameters() {
		$players = array();
		
		if ($this->_websoccer->getRequestParameter("loadformdata")) {
			$players = PlayersDataService::getPlayersOfTeamByPosition($this->_websoccer, $this->_db,
					 $this->_websoccer->getUser()->getClubId($this->_websoccer, $this->_db));
		}
		
		return array("players" => $players, "player" => $this->_player);
	}
	
}

?>