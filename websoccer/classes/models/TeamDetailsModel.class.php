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

class TeamDetailsModel implements IModel {
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
		
		$teamId = (int) $this->_websoccer->getRequestParameter('id');
		if ($teamId < 1) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$team = TeamsDataService::getTeamById($this->_websoccer, $this->_db, $teamId);
		if (!isset($team['team_id'])) {
			throw new Exception($this->_i18n->getMessage(MSG_KEY_ERROR_PAGENOTFOUND));
		}
		
		$stadium = StadiumsDataService::getStadiumByTeamId($this->_websoccer, $this->_db, $teamId);
		
		// compute strength level of national team
		if ($team['is_nationalteam']) {
			$dbPrefix = $this->_websoccer->getConfig('db_prefix') ;
			$result = $this->_db->querySelect('AVG(P.w_staerke) AS avgstrength', 
					$dbPrefix . '_spieler AS P INNER JOIN ' . $dbPrefix . '_nationalplayer AS NP ON P.id = NP.player_id', 
					'NP.team_id = %d', $team['team_id']);
			$players = $result->fetch_array();
			$result->free();
			if ($players) {
				$team['team_strength'] = $players['avgstrength'];
			}
		}
		
		if (!$team['is_nationalteam']) {
			$playerfacts = $this->getPlayerFacts($teamId);
		} else {
			$playerfacts = array();
		}
		
		$team['victories'] = $this->getVictories($team['team_id'], $team['team_league_id']);
		$team['cupvictories'] = $this->getCupVictories($team['team_id']);
		return array('team' => $team, 'stadium' => $stadium, 'playerfacts' => $playerfacts);
	}
	
	private function getVictories($teamId, $leagueId) {
		
		$fromTable = $this->_websoccer->getConfig('db_prefix') .'_saison AS S INNER JOIN ' . $this->_websoccer->getConfig('db_prefix') . '_liga AS L ON L.id = S.liga_id';
		
		$columns['S.name'] = 'season_name';
		$columns['L.name'] = 'league_name';
		$columns['platz_1_id'] = 'season_first';
		$columns['platz_2_id'] = 'season_second';
		$columns['platz_3_id'] = 'season_third';
		$columns['platz_4_id'] = 'season_fourth';
		$columns['platz_5_id'] = 'season_fivth';
		
		$whereCondition = 'beendet = 1 AND (platz_1_id = %d OR platz_2_id = %d OR platz_3_id = %d OR platz_4_id = %d OR platz_5_id = %d)';
		$parameters = array($teamId, $teamId, $teamId, $teamId, $teamId);
		
		$victories = array();
		
		$result = $this->_db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		while ($season = $result->fetch_array()) {
			$place = 1;
			if ($season['season_second'] == $teamId) {
				$place = 2;
			} else if ($season['season_third'] == $teamId) {
				$place = 3;
			} else if ($season['season_fourth'] == $teamId) {
				$place = 4;
			} else if ($season['season_fivth'] == $teamId) {
				$place = 5;
			}
			
			$victories[] = array('season_name' => $season['season_name'], 'season_place' => $place, 'league_name' => $season['league_name']);
		}
		$result->free();
		return $victories;
	}
	
	private function getCupVictories($teamId) {
		$fromTable = $this->_websoccer->getConfig('db_prefix') .'_cup';
		$whereCondition = 'winner_id = %d ORDER BY name ASC';
		$result = $this->_db->querySelect('id AS cup_id,name AS cup_name,logo AS cup_logo', $fromTable, $whereCondition, $teamId);
		
		$victories = array();
		while ($cup = $result->fetch_array()) {
			$victories[] = $cup;;
		}
		$result->free();
		return $victories;
	}
	
	private function getPlayerFacts($teamId) {
		$columns = array(
				'COUNT(*)' => 'numberOfPlayers'
				);
		
		// age
		if ($this->_websoccer->getConfig('players_aging') == 'birthday') {
			$ageColumn = 'TIMESTAMPDIFF(YEAR,geburtstag,CURDATE())';
		} else {
			$ageColumn = 'age';
		}
		$columns['AVG(' . $ageColumn . ')'] = 'avgAge';
		
		// marketvalue
		if ($this->_websoccer->getConfig('transfermarket_computed_marketvalue')) {
			$columns['SUM(w_staerke)'] = 'sumStrength';
			$columns['SUM(w_technik)'] = 'sumTechnique';
			$columns['SUM(w_frische)'] = 'sumFreshness';
			$columns['SUM(w_zufriedenheit)'] = 'sumSatisfaction';
			$columns['SUM(w_kondition)'] = 'sumStamina';
		} else {
			$columns['SUM(marktwert)'] = 'sumMarketValue';
		}
		
		$result = $this->_db->querySelect($columns, $this->_websoccer->getConfig('db_prefix') .'_spieler', 'verein_id = %d AND status = \'1\'', $teamId);
		$playerfacts = $result->fetch_array();
		$result->free();
		
		if ($this->_websoccer->getConfig('transfermarket_computed_marketvalue')) {
			$playerfacts['sumMarketValue'] = $this->computeMarketValue($playerfacts['sumStrength'], $playerfacts['sumTechnique'],
					$playerfacts['sumFreshness'], $playerfacts['sumSatisfaction'], $playerfacts['sumStamina']);
		}
		if ($playerfacts['numberOfPlayers'] > 0) {
			$playerfacts['avgMarketValue'] = $playerfacts['sumMarketValue'] / $playerfacts['numberOfPlayers'];
		} else {
			$playerfacts['avgMarketValue'] = 0;
		}
		
		
		return $playerfacts;
	}
	
	private function computeMarketValue($strength, $technique, $freshness, $satisfaction, $stamina) {
	
		$weightStrength = $this->_websoccer->getConfig('sim_weight_strength');
		$weightTech = $this->_websoccer->getConfig('sim_weight_strengthTech');
		$weightStamina = $this->_websoccer->getConfig('sim_weight_strengthStamina');
		$weightFreshness = $this->_websoccer->getConfig('sim_weight_strengthFreshness');
		$weightSatisfaction = $this->_websoccer->getConfig('sim_weight_strengthSatisfaction');
		
		$totalStrength = $weightStrength * $strength;
		$totalStrength += $weightTech * $technique;
		$totalStrength += $weightStamina * $freshness;
		$totalStrength += $weightFreshness * $satisfaction;
		$totalStrength += $weightSatisfaction * $stamina;
	
		$totalStrength /= $weightStrength + $weightTech + $weightStamina + $weightFreshness + $weightSatisfaction;
	
		return $totalStrength * $this->_websoccer->getConfig('transfermarket_value_per_strength');
	}
	
}

?>