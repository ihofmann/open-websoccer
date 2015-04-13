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
 * Data service for matches.
 */
class MatchesDataService {
	
	public static function getNextMatches(WebSoccer $websoccer, DbConnection $db, $clubId, $maxNumber) {
		$fromTable = self::_getFromPart($websoccer);
	
		// where
		$whereCondition = 'M.berechnet != \'1\' AND (HOME.id = %d OR GUEST.id = %d) AND M.datum > %d ORDER BY M.datum ASC';
		$parameters = array($clubId, $clubId, $websoccer->getNowAsTimestamp());
	
		// select
		$columns['M.id'] = 'match_id';
		$columns['M.datum'] = 'match_date';
		$columns['M.spieltyp'] = 'match_type';
		$columns['HOME.id'] = 'match_home_id';
		$columns['HOME.name'] = 'match_home_name';
		$columns['GUEST.id'] = 'match_guest_id';
		$columns['GUEST.name'] = 'match_guest_name';
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $maxNumber);
		$matches = array();
		while ($match = $result->fetch_array()) {
			$match['match_type'] = self::_convertLeagueType($match['match_type']);
			$matches[] = $match;
		}
		$result->free();
	
		return $matches;
	}

	public static function getNextMatch(WebSoccer $websoccer, DbConnection $db, $clubId) {
		$fromTable = self::_getFromPart($websoccer);
		$formationTable = $websoccer->getConfig('db_prefix') . '_aufstellung';
		$fromTable .= ' LEFT JOIN ' . $formationTable . ' AS HOME_F ON HOME_F.verein_id = HOME.id AND HOME_F.match_id = M.id';
		$fromTable .= ' LEFT JOIN ' . $formationTable . ' AS GUEST_F ON GUEST_F.verein_id = GUEST.id AND GUEST_F.match_id = M.id';
		
		// where
		$whereCondition = 'M.berechnet != \'1\' AND (HOME.id = %d OR GUEST.id = %d) AND M.datum > %d ORDER BY M.datum ASC';
		$parameters = array($clubId, $clubId, $websoccer->getNowAsTimestamp());
		
		// select
		$columns['M.id'] = 'match_id';
		$columns['M.datum'] = 'match_date';
		$columns['M.spieltyp'] = 'match_type';
		$columns['HOME.id'] = 'match_home_id';
		$columns['HOME.name'] = 'match_home_name';
		$columns['HOME_F.id'] = 'match_home_formation_id';
		$columns['GUEST.id'] = 'match_guest_id';
		$columns['GUEST.name'] = 'match_guest_name';
		$columns['GUEST_F.id'] = 'match_guest_formation_id';
		
		$matchinfos = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters, 1);
		
		if (!count($matchinfos)) {
			$matchinfo = array();
		} else {
			$matchinfo = $matchinfos[0];
			$matchinfo['match_type'] = self::_convertLeagueType($matchinfo['match_type']);
		}
		
		return $matchinfo;
	}
	
	public static function getLiveMatch(WebSoccer $websoccer, DbConnection $db) {
		$fromTable = self::_getFromPart($websoccer);
	
		// where
		$whereCondition = 'M.berechnet != \'1\' AND M.minutes > 0 AND (HOME.user_id = %d OR GUEST.user_id = %d) AND M.datum < %d ORDER BY M.datum DESC';
		$parameters = array($websoccer->getUser()->id, $websoccer->getUser()->id, $websoccer->getNowAsTimestamp());
	
		// select
		$columns['M.id'] = 'match_id';
		$columns['M.datum'] = 'match_date';
		$columns['M.spieltyp'] = 'match_type';
		$columns['HOME.id'] = 'match_home_id';
		$columns['HOME.name'] = 'match_home_name';
		$columns['GUEST.id'] = 'match_guest_id';
		$columns['GUEST.name'] = 'match_guest_name';
	
		$matchinfos = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters, 1);
	
		if (!count($matchinfos)) {
			$matchinfo = array();
		} else {
			$matchinfo = $matchinfos[0];
			$matchinfo['match_type'] = self::_convertLeagueType($matchinfo['match_type']);
		}
	
		return $matchinfo;
	}
	
	public static function getMatchById(WebSoccer $websoccer, DbConnection $db, $matchId, $loadStadiumInfo = TRUE, $loadSeasonInfo = FALSE) {
		$fromTable = self::_getFromPart($websoccer);
		
		if ($loadStadiumInfo) {
			$fromTable .= ' LEFT JOIN '. $websoccer->getConfig('db_prefix') . '_stadion AS S ON  S.id = IF(M.stadion_id IS NOT NULL, M.stadion_id, HOME.stadion_id)';
			$columns['S.name'] = 'match_stadium_name';
		}
		
		if ($loadSeasonInfo) {
			$fromTable .= ' LEFT JOIN '. $websoccer->getConfig('db_prefix') . '_saison AS SEASON ON SEASON.id = M.saison_id';
			$columns['SEASON.name'] = 'match_season_name';
			$columns['SEASON.liga_id'] = 'match_league_id';
		}
	
		// where
		$whereCondition = 'M.id = %d';
		$parameters = $matchId;
	
		// select
		$columns['M.id'] = 'match_id';
		$columns['M.datum'] = 'match_date';
		$columns['M.spieltyp'] = 'match_type';
		$columns['HOME.id'] = 'match_home_id';
		$columns['HOME.name'] = 'match_home_name';
		$columns['HOME.nationalteam'] = 'match_home_nationalteam';
		$columns['HOME.bild'] = 'match_home_picture';
		$columns['GUEST.id'] = 'match_guest_id';
		$columns['GUEST.name'] = 'match_guest_name';
		$columns['GUEST.nationalteam'] = 'match_guest_nationalteam';
		$columns['GUEST.bild'] = 'match_guest_picture';
		$columns['M.pokalname'] = 'match_cup_name';
		$columns['M.pokalrunde'] = 'match_cup_round';
		$columns['M.spieltag'] = 'match_matchday';
		$columns['M.saison_id'] = 'match_season_id';
		$columns['M.berechnet'] = 'match_simulated';
		$columns['M.home_tore'] = 'match_goals_home';
		$columns['M.gast_tore'] = 'match_goals_guest';
		$columns['M.bericht'] = 'match_deprecated_report';
		$columns['M.minutes'] = 'match_minutes';
		$columns['M.home_noformation'] = 'match_home_noformation';
		$columns['M.guest_noformation'] = 'match_guest_noformation';
		$columns['M.zuschauer'] = 'match_audience';
		$columns['M.soldout'] = 'match_soldout';
		$columns['M.elfmeter'] = 'match_penalty_enabled';
		$columns['M.home_offensive'] = 'match_home_offensive';
		$columns['M.gast_offensive'] = 'match_guest_offensive';
		$columns['M.home_longpasses'] = 'match_home_longpasses';
		$columns['M.gast_longpasses'] = 'match_guest_longpasses';
		$columns['M.home_counterattacks'] = 'match_home_counterattacks';
		$columns['M.gast_counterattacks'] = 'match_guest_counterattacks';
		
		// data about substitutions
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			$columns['M.home_w' . $subNo . '_raus'] = 'match_home_sub' . $subNo . '_out';
			$columns['M.home_w' . $subNo . '_rein'] = 'match_home_sub' . $subNo . '_in';
			$columns['M.home_w' . $subNo . '_minute'] = 'match_home_sub' . $subNo . '_minute';
			$columns['M.home_w' . $subNo . '_condition'] = 'match_home_sub' . $subNo . '_condition';
			
			$columns['M.gast_w' . $subNo . '_raus'] = 'match_guest_sub' . $subNo . '_out';
			$columns['M.gast_w' . $subNo . '_rein'] = 'match_guest_sub' . $subNo . '_in';
			$columns['M.gast_w' . $subNo . '_minute'] = 'match_guest_sub' . $subNo . '_minute';
			$columns['M.gast_w' . $subNo . '_condition'] = 'match_guest_sub' . $subNo . '_condition';
		}
		
		$matchinfos = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$match = (isset($matchinfos[0])) ? $matchinfos[0] : array();
		
		if (isset($match['match_type'])) {
			$match['match_type'] = self::_convertLeagueType($match['match_type']);
		}
		
		return $match;
	}
	
	public static function getMatchSubstitutionsById(WebSoccer $websoccer, DbConnection $db, $matchId) {
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel AS M';
	
		// where
		$whereCondition = 'M.id = %d';
		$parameters = $matchId;
	
		// select
		$columns['M.id'] = 'match_id';
		$columns['M.home_verein'] = 'match_home_id';
		$columns['M.gast_verein'] = 'match_guest_id';
		$columns['M.berechnet'] = 'match_simulated';
		$columns['M.minutes'] = 'match_minutes';
		
		$columns['M.home_offensive'] = 'match_home_offensive';
		$columns['M.home_offensive_changed'] = 'match_home_offensive_changed';
		$columns['M.home_longpasses'] = 'match_home_longpasses';
		$columns['M.home_counterattacks'] = 'match_home_counterattacks';
		$columns['M.home_freekickplayer'] = 'match_home_freekickplayer';
		
		$columns['M.gast_offensive_changed'] = 'match_guest_offensive_changed';
		$columns['M.gast_offensive'] = 'match_guest_offensive';
		$columns['M.gast_longpasses'] = 'match_guest_longpasses';
		$columns['M.gast_counterattacks'] = 'match_guest_counterattacks';
		$columns['M.gast_freekickplayer'] = 'match_guest_freekickplayer';
		
		for ($subNo = 1; $subNo <= 3; $subNo++) {
			$columns['M.home_w'. $subNo . '_raus'] = 'home_sub'. $subNo . '_out';
			$columns['M.home_w'. $subNo . '_rein'] = 'home_sub'. $subNo . '_in';
			$columns['M.home_w'. $subNo . '_minute'] = 'home_sub'. $subNo . '_minute';
			$columns['M.home_w'. $subNo . '_condition'] = 'home_sub'. $subNo . '_condition';
			$columns['M.home_w'. $subNo . '_position'] = 'home_sub'. $subNo . '_position';
			
			$columns['M.gast_w'. $subNo . '_raus'] = 'guest_sub'. $subNo . '_out';
			$columns['M.gast_w'. $subNo . '_rein'] = 'guest_sub'. $subNo . '_in';
			$columns['M.gast_w'. $subNo . '_minute'] = 'guest_sub'. $subNo . '_minute';
			$columns['M.gast_w'. $subNo . '_condition'] = 'guest_sub'. $subNo . '_condition';
			$columns['M.gast_w'. $subNo . '_position'] = 'guest_sub'. $subNo . '_position';
		}
	
		$matchinfos = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$match = (isset($matchinfos[0])) ? $matchinfos[0] : array();
	
		return $match;
	}
	
	public static function getLastMatch(WebSoccer $websoccer, DbConnection $db) {
		// where
		$whereCondition = 'M.berechnet = 1 AND (HOME.user_id = %d OR GUEST.user_id = %d) AND M.datum < %d ORDER BY M.datum DESC';
		$parameters = array($websoccer->getUser()->id, $websoccer->getUser()->id, $websoccer->getNowAsTimestamp());
	
		return self::_getMatchSummaryByCondition($websoccer, $db, $whereCondition, $parameters);
	}
	
	public static function getLiveMatchByTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		// where
		$whereCondition = 'M.berechnet != 1 AND (HOME.id = %d OR GUEST.id = %d) AND M.minutes > 0 ORDER BY M.datum DESC';
		$parameters = array($teamId, $teamId);
	
		return self::_getMatchSummaryByCondition($websoccer, $db, $whereCondition, $parameters);
	}
	
	private static function _getMatchSummaryByCondition(WebSoccer $websoccer, DbConnection $db, $whereCondition, $parameters) {
		$fromTable = self::_getFromPart($websoccer);
	
		// select
		$columns['M.id'] = 'match_id';
		$columns['M.datum'] = 'match_date';
		$columns['M.spieltyp'] = 'match_type';
		$columns['HOME.id'] = 'match_home_id';
		$columns['HOME.name'] = 'match_home_name';
		$columns['GUEST.id'] = 'match_guest_id';
		$columns['GUEST.name'] = 'match_guest_name';
		$columns['M.home_tore'] = 'match_goals_home';
		$columns['M.gast_tore'] = 'match_goals_guest';
	
		$matchinfos = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters, 1);
	
		if (!count($matchinfos)) {
			$matchinfo = array();
		} else {
			$matchinfo = $matchinfos[0];
			$matchinfo['match_type'] = self::_convertLeagueType($matchinfo['match_type']);
		}
	
		return $matchinfo;
	}
	
	public static function getPreviousMatches($matchinfo, WebSoccer $websoccer, DbConnection $db) {
		$fromTable = self::_getFromPart($websoccer);
		
		// where
		$whereCondition = 'M.berechnet = 1 AND (HOME.id = %d AND GUEST.id = %d OR HOME.id = %d AND GUEST.id = %d) ORDER BY M.datum DESC';
		$parameters = array($matchinfo['match_home_id'], $matchinfo['match_guest_id'], $matchinfo['match_guest_id'], $matchinfo['match_home_id']);
		
		// select
		$columns['M.id'] = 'id';
		$columns['HOME.name'] = 'home_team';
		$columns['GUEST.name'] = 'guest_team';
		$columns['M.home_tore'] = 'home_goals';
		$columns['M.gast_tore'] = 'guest_goals';
		
		$matches = array();
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 4);
		while ($matchinfo = $result->fetch_array()) {
			$matches[] = $matchinfo;
		}
		$result->free();
		return $matches;
	}
	
	public static function getCupRoundsByCupname(WebSoccer $websoccer, DbConnection $db) {
		
		$columns['C.name'] = 'cup';
		$columns['R.name'] = 'round';
		$columns['R.firstround_date'] = 'round_date';
		
		// get rounds from matches
		$fromTable = $websoccer->getConfig('db_prefix') . '_cup_round AS R ';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_cup AS C ON C.id = R.cup_id';
		$result = $db->querySelect($columns, $fromTable, 'archived != \'1\' ORDER BY cup ASC, round_date ASC');
		
		$cuprounds = array();
		while ($cup = $result->fetch_array()) {
			$cuprounds[$cup['cup']][] = $cup['round'];
		}
		$result->free();
		
		return $cuprounds;
	}
	
	public static function getMatchesByMatchday(WebSoccer $websoccer, DbConnection $db, $seasonId, $matchDay) {
		// where
		$whereCondition = 'M.saison_id = %d AND M.spieltag = %d  ORDER BY M.datum ASC';
		$parameters = array($seasonId, $matchDay);
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, 50);
	}
	
	public static function getMatchesByCupRound(WebSoccer $websoccer, DbConnection $db, $cupName, $cupRound) {
		// where
		$whereCondition = 'M.pokalname = \'%s\' AND M.pokalrunde = \'%s\'  ORDER BY M.datum ASC';
		$parameters = array($cupName, $cupRound);
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, 50);
	}
	
	public static function getMatchesByCupRoundAndGroup(WebSoccer $websoccer, DbConnection $db, $cupName, $cupRound, $cupGroup) {
		// where
		$whereCondition = 'M.pokalname = \'%s\' AND M.pokalrunde = \'%s\' AND M.pokalgruppe = \'%s\' ORDER BY M.datum ASC';
		$parameters = array($cupName, $cupRound, $cupGroup);
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, 50);
	}
	
	public static function getLatestMatches(WebSoccer $websoccer, DbConnection $db, $limit = 20, $ignoreFriendlies = FALSE) {
		// where
		$whereCondition = 'M.berechnet = 1';
		if ($ignoreFriendlies) {
			$whereCondition .= ' AND M.spieltyp != \'Freundschaft\'';
		}
		$whereCondition .= ' ORDER BY M.datum DESC';
		$parameters = array();
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, $limit);
	}
	
	public static function getLatestMatchesByUser(WebSoccer $websoccer, DbConnection $db, $userId) {
		// where
		$whereCondition = 'M.berechnet = 1 AND (M.home_user_id = %d OR M.gast_user_id = %d) ORDER BY M.datum DESC';
		$parameters = array($userId, $userId);
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, 20);
	}
	
	public static function getLatestMatchesByTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		// where
		$whereCondition = 'M.berechnet = 1 AND (HOME.id = %d OR GUEST.id = %d) ORDER BY M.datum DESC';
		$parameters = array($teamId, $teamId);
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, 20);
	}
	
	public static function getTodaysMatches(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
		
		$startTs = mktime (0, 0, 1, date('n'), date('j'), date('Y'));
		$endTs = $startTs + 3600 * 24;
		
		// where
		$whereCondition = 'M.datum >= %d AND M.datum < %d ORDER BY M.datum ASC';
		$parameters = array($startTs, $endTs);
	
		$limit = $startIndex .','. $entries_per_page;
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, $limit);
	}
	
	public static function countTodaysMatches(WebSoccer $websoccer, DbConnection $db) {
	
		$startTs = mktime (0, 0, 1, date('n'), date('j'), date('Y'));
		$endTs = $startTs + 3600 * 24;
	
		// where
		$whereCondition = 'M.datum >= %d AND M.datum < %d';
		$parameters = array($startTs, $endTs);
		
		$result = $db->querySelect('COUNT(*) AS hits', $websoccer->getConfig('db_prefix') . '_spiel AS M', $whereCondition, $parameters);
		$matches = $result->fetch_array();
		$result->free();
		
		if ($matches) {
			return $matches['hits'];
		}
	
		return 0;
	}
	
	
	public static function getMatchesByTeamAndTimeframe(WebSoccer $websoccer, DbConnection $db, $teamId, $dateStart, $dateEnd) {
		// where
		$whereCondition = '(HOME.id = %d OR GUEST.id = %d) AND datum >= %d AND datum <= %d ORDER BY M.datum DESC';
		$parameters = array($teamId, $teamId, $dateStart, $dateEnd);
	
		return self::getMatchesByCondition($websoccer, $db, $whereCondition, $parameters, 20);
	}
	
	public static function getMatchdayNumberOfTeam(WebSoccer $websoccer, DbConnection $db, $teamId) {
		$columns = 'spieltag AS matchday';
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel';
		$whereCondition = 'spieltyp = \'Ligaspiel\' AND berechnet = 1 AND (home_verein = %d OR gast_verein = %d) ORDER BY datum DESC';
		$parameters = array($teamId, $teamId);
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$matches = $result->fetch_array();
		$result->free();
		
		if ($matches) {
			return (int) $matches['matchday'];
		}
		
		return 0;
	}
	
	public static function getMatchReportPlayerRecords(WebSoccer $websoccer, DbConnection $db, $matchId, $teamId) {
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel_berechnung AS M';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_spieler AS P ON P.id = M.spieler_id';
		
		$columns['P.id'] = 'id';
		$columns['P.vorname'] = 'firstName';
		$columns['P.nachname'] = 'lastName';
		$columns['P.kunstname'] = 'pseudonym';
		$columns['P.position'] = 'position';
		
		$columns['M.position_main'] = 'position_main';
		
		$columns['M.note'] = 'grade';
		$columns['M.tore'] = 'goals';
		$columns['M.verletzt'] = 'injured';
		$columns['M.gesperrt'] = 'blocked';
		$columns['M.karte_gelb'] = 'yellowCards';
		$columns['M.karte_rot'] = 'redCard';
		$columns['M.feld'] = 'playstatus';
		$columns['M.minuten_gespielt'] = 'minutesPlayed';
		$columns['M.assists'] = 'assists';
		$columns['M.ballcontacts'] = 'ballcontacts';
		$columns['M.wontackles'] = 'wontackles';
		$columns['M.losttackles'] = 'losttackles';
		$columns['M.shoots'] = 'shoots';
		$columns['M.passes_successed'] = 'passes_successed';
		$columns['M.passes_failed'] = 'passes_failed';
		$columns['M.age'] = 'age';
		$columns['M.w_staerke'] = 'strength';
		
		$order = 'field(M.position_main, \'T\', \'LV\', \'IV\', \'RV\', \'DM\', \'LM\', \'ZM\', \'RM\', \'OM\', \'LS\', \'MS\', \'RS\')';
		$whereCondition = 'M.spiel_id = %d AND M.team_id = %d AND M.feld != \'Ersatzbank\' ORDER BY ' . $order . ', M.id ASC';
		$parameters = array($matchId, $teamId);
		
		$players = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters);
		return $players;
	}
	
	public static function getMatchPlayerRecordsByField(WebSoccer $websoccer, DbConnection $db, $matchId, $teamId) {
	
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel_berechnung AS M';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_spieler AS P ON P.id = M.spieler_id';
		
		$columns = array(
				'P.id' => 'id',
				'P.vorname' => 'firstname',
				'P.nachname' => 'lastname',
				'P.kunstname' => 'pseudonym',
				'P.verletzt' => 'matches_injured',
				'P.position' => 'position',
				'P.position_main' => 'position_main',
				'P.position_second' => 'position_second',
				'P.w_staerke' => 'strength',
				'P.w_technik' => 'strength_technique',
				'P.w_kondition' => 'strength_stamina',
				'P.w_frische' => 'strength_freshness',
				'P.w_zufriedenheit' => 'strength_satisfaction',
				'P.nation' => 'player_nationality',
				'P.picture' => 'picture',
				'P.sa_tore' => 'st_goals',
				'P.sa_spiele' => 'st_matches',
				'P.sa_karten_gelb' => 'st_cards_yellow',
				'P.sa_karten_gelb_rot' => 'st_cards_yellow_red',
				'P.sa_karten_rot' => 'st_cards_red',
				'M.id' => 'match_record_id',
				'M.position' => 'match_position',
				'M.position_main' => 'match_position_main',
				'M.feld' => 'field',
				'M.note' => 'grade'
		);
		
		if ($websoccer->getConfig('players_aging') == 'birthday') {
			$ageColumn = 'TIMESTAMPDIFF(YEAR,P.geburtstag,CURDATE())';
		} else {
			$ageColumn = 'P.age';
		}
		$columns[$ageColumn] = 'age';
		
		$whereCondition = 'M.spiel_id = %d AND M.team_id = %d AND M.feld != \'Ausgewechselt\' ORDER BY field(M.position_main, \'T\', \'LV\', \'IV\', \'RV\', \'DM\', \'LM\', \'ZM\', \'RM\', \'OM\', \'LS\', \'MS\', \'RS\'), M.id ASC';
		$parameters = array($matchId, $teamId);
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$players = array();
		while ($player = $result->fetch_array()) {
			$field = ($player['field'] === '1') ? 'field' : 'bench';
			$player['position'] = PlayersDataService::_convertPosition($player['position']);
			$players[$field][] = $player;
			
		}
		$result->free();
	
		return $players;
	}
	
	public static function getMatchReportMessages(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $matchId) {
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_matchreport AS R';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_spiel_text AS T ON R.message_id = T.id';
		
		$columns['R.id'] = 'report_id';
		$columns['R.minute'] = 'minute';
		$columns['R.playernames'] = 'playerNames';
		$columns['R.goals'] = 'goals';
		$columns['T.nachricht'] = 'message';
		$columns['T.aktion'] = 'type';
		$columns['R.active_home'] = 'active_home';
		
		$whereCondition = 'R.match_id = %d ORDER BY R.minute DESC, R.id DESC';
		$parameters = $matchId;
		
		$reportmessages = array();
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$match = null; // required only for team name replacements
		$removeSlashes = get_magic_quotes_gpc();
		while ($reportmessage = $result->fetch_array()) {
			
			// replace placeholders
			$players = explode(';', $reportmessage['playerNames']);
			$rmsg = $reportmessage['message'];
			
			// remove escaping slashes
			if ($removeSlashes) {
				$rmsg = stripslashes($rmsg);
			}
			
			$msgKey = strip_tags($rmsg);
			if ($i18n->hasMessage($msgKey)) {
				$rmsg = $i18n->getMessage($msgKey);
			}
			
			for($playerIndex = 1; $playerIndex <= count($players); $playerIndex++) {
				$rmsg = str_replace('{sp' . $playerIndex . '}', $players[$playerIndex - 1], $rmsg);
			}
			
			// replace team name placeholders
			if (strpos($rmsg, '{ma1}') || strpos($rmsg, '{ma2}')) {
				if ($match == null) {
					$match = self::getMatchById($websoccer, $db, $matchId, FALSE);
				}
				
				if ($reportmessage['active_home']) {
					$rmsg = str_replace('{ma1}', $match['match_home_name'], $rmsg);
					$rmsg = str_replace('{ma2}', $match['match_guest_name'], $rmsg);
				} else {
					$rmsg = str_replace('{ma1}', $match['match_guest_name'], $rmsg);
					$rmsg = str_replace('{ma2}', $match['match_home_name'], $rmsg);
				}
				
			}
			
			
			$reportmessage['message'] = $rmsg;
			$reportmessages[] = $reportmessage;
		}
		$result->free();
		
		return $reportmessages;
	}
	
	/**
	 * 
	 * @param WebSoccer $websoccer
	 * @param DbConnection $db
	 * @param string $whereCondition
	 * @param array $parameters
	 * @param string $limit
	 * @return array list of matches.
	 */
	public static function getMatchesByCondition(WebSoccer $websoccer, DbConnection $db, $whereCondition, $parameters, $limit) {
		$fromTable = self::_getFromPart($websoccer);
		
		// select
		$columns['M.id'] = 'id';
		$columns['M.spieltyp'] = 'type';
		$columns['M.pokalname'] = 'cup_name';
		$columns['M.pokalrunde'] = 'cup_round';
		$columns['M.home_noformation'] = 'home_noformation';
		$columns['M.guest_noformation'] = 'guest_noformation';
		$columns['HOME.name'] = 'home_team';
		$columns['HOME.bild'] = 'home_team_picture';
		$columns['HOME.id'] = 'home_id';
		$columns['HOMEUSER.id'] = 'home_user_id';
		$columns['HOMEUSER.nick'] = 'home_user_nick';
		$columns['HOMEUSER.email'] = 'home_user_email';
		$columns['HOMEUSER.picture'] = 'home_user_picture';
		$columns['GUEST.name'] = 'guest_team';
		$columns['GUEST.bild'] = 'guest_team_picture';
		$columns['GUEST.id'] = 'guest_id';
		$columns['GUESTUSER.id'] = 'guest_user_id';
		$columns['GUESTUSER.nick'] = 'guest_user_nick';
		$columns['GUESTUSER.email'] = 'guest_user_email';
		$columns['GUESTUSER.picture'] = 'guest_user_picture';
		$columns['M.home_tore'] = 'home_goals';
		$columns['M.gast_tore'] = 'guest_goals';
		$columns['M.berechnet'] = 'simulated';
		$columns['M.minutes'] = 'minutes';
		$columns['M.datum'] = 'date';
		
		$matches = array();
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, $limit);
		while ($matchinfo = $result->fetch_array()) {
			$matchinfo['home_user_picture'] = UsersDataService::getUserProfilePicture($websoccer, $matchinfo['home_user_picture'], $matchinfo['home_user_email']);
			$matchinfo['guest_user_picture'] = UsersDataService::getUserProfilePicture($websoccer, $matchinfo['guest_user_picture'], $matchinfo['guest_user_email']);
			$matches[] = $matchinfo;
		}
		$result->free();
		return $matches;
	}
	
	private static function _getFromPart(WebSoccer $websoccer) {
		$tablePrefix = $websoccer->getConfig('db_prefix');
		
		// from
		$fromTable = $tablePrefix . '_spiel AS M';
		$fromTable .= ' INNER JOIN ' . $tablePrefix . '_verein AS HOME ON M.home_verein = HOME.id';
		$fromTable .= ' INNER JOIN ' . $tablePrefix . '_verein AS GUEST ON M.gast_verein = GUEST.id';
		$fromTable .= ' LEFT JOIN ' . $tablePrefix . '_user AS HOMEUSER ON M.home_user_id = HOMEUSER.id';
		$fromTable .= ' LEFT JOIN ' . $tablePrefix . '_user AS GUESTUSER ON M.gast_user_id = GUESTUSER.id';
		return $fromTable;
	}
	
	private static function _convertLeagueType($dbValue) {
		switch ($dbValue) {
			case 'Ligaspiel':
				return 'league';
			case 'Pokalspiel':
				return 'cup';
			case 'Freundschaft':
				return 'friendly';
		}
	}
}
?>