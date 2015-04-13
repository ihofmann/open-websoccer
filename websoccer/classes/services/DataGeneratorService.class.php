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
define('FILE_CITYNAMES', BASE_FOLDER . '/admin/config/names/%s/cities.txt');
define('FILE_PREFIXNAMES', BASE_FOLDER . '/admin/config/names/%s/clubprefix.txt');
define('FILE_SUFFIXNAMES', BASE_FOLDER . '/admin/config/names/%s/clubsuffix.txt');
define('FILE_FIRSTNAMES', BASE_FOLDER . '/admin/config/names/%s/firstnames.txt');
define('FILE_LASTNAMES', BASE_FOLDER . '/admin/config/names/%s/lastnames.txt');

/**
 * Service for generating random game data records (teams, players).
 */
class DataGeneratorService {
	
	/**
	 * Generates new football teams for a specified league.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection-
	 * @param int $numberOfTeams number of teams to generate.
	 * @param int $leagueId ID of league to generate teams for.
	 * @param int $budget Initial budget of each team.
	 * @param boolean $generateStadium TRUE if stadium shall be generates as well.
	 * @param string $stadiumNamePattern name of stadium with '%s' as placeholder for city name.
	 * @param int $stadiumStands number of places
	 * @param int $stadiumSeats number of places
	 * @param int $stadiumStandsGrand number of places
	 * @param int $stadiumSeatsGrand number of places
	 * @param int $stadiumVip number of places
	 * @throws Exception if generation failes.
	 */
	public static function generateTeams(WebSoccer $websoccer, DbConnection $db, $numberOfTeams, $leagueId, $budget,
			$generateStadium, $stadiumNamePattern, $stadiumStands, $stadiumSeats, $stadiumStandsGrand, $stadiumSeatsGrand, $stadiumVip) {
		
		// get country
		$result = $db->querySelect('*', $websoccer->getConfig('db_prefix') . '_liga', 'id = %d', $leagueId);
		$league = $result->fetch_array();
		$result->free();
		
		if (!$league) {
			throw new Exception('illegal league ID');
		}
		
		$country = $league['land'];
		
		$cities = self::_getLines(FILE_CITYNAMES, $country);
		$prefixes = self::_getLines(FILE_PREFIXNAMES, $country);
		$suffixes = array();
		try {
			$suffixes = self::_getLines(FILE_SUFFIXNAMES, $country);
		} catch(Exception $e) {
			// no suffix file exist or is empty, so we will take only prefixes.
		}
		
		// create teams
		for ($teamNo = 1; $teamNo <= $numberOfTeams; $teamNo++) {
			$cityName = self::_getItemFromArray($cities);
			self::_createTeam($websoccer, $db, $league, $country, $cityName, $prefixes, $suffixes, $budget,
					$generateStadium, $stadiumNamePattern, $stadiumStands, $stadiumSeats, $stadiumStandsGrand, $stadiumSeatsGrand, $stadiumVip);
		}
	}
	
	/**
	 * Generates new players for a specified team.
	 * 
	 * @param WebSoccer $websoccer Application context
	 * @param DbConnection $db DB connection
	 * @param int $teamId ID of team to generate players for. If 0, then players will be generated for transfer market.
	 * @param int $age age of players in years.
	 * @param int $ageDeviation maximum deviation of age in years.
	 * @param int $salary salary per match.
	 * @param int $contractDuration contract duration in number of matches.
	 * @param array $strengths assoc. array of strength values. Keys are: strength, technique, stamina, freshness, satisfaction
	 * @param array $positions assoc array of positions and number of players to generate for each position. Key=abbreviated positions as in DB.
	 * @param int $maxDeviation maximum deviation in strength.
	 * @param sring|NULL $nationality optional. Nationality of player.If not provided, it is taken from club.
	 * @throws Exception if generation failed.
	 */
	public static function generatePlayers(WebSoccer $websoccer, DbConnection $db, $teamId, $age, $ageDeviation, $salary, $contractDuration, $strengths, $positions, $maxDeviation, $nationality = NULL) {
		
		if (strlen($nationality)) {
			$country = $nationality;
		} else {
			// get country from team
			$fromTable = $websoccer->getConfig('db_prefix') . '_verein AS T';
			$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_liga AS L ON L.id = T.liga_id';
			$result = $db->querySelect('L.land AS country', $fromTable, 'T.id = %d', $teamId);
			$league = $result->fetch_array();
			$result->free();
			
			if (!$league) {
				throw new Exception('illegal team ID');
			}
			
			$country = $league['country'];
		}

		
		$firstNames = self::_getLines(FILE_FIRSTNAMES, $country);
		$lastNames = self::_getLines(FILE_LASTNAMES, $country);
		
		// map main position to parent position
		$mainPositions['T'] = 'Torwart';
		$mainPositions['LV'] = 'Abwehr';
		$mainPositions['IV'] = 'Abwehr';
		$mainPositions['RV'] = 'Abwehr';
		$mainPositions['LM'] = 'Mittelfeld';
		$mainPositions['ZM'] = 'Mittelfeld';
		$mainPositions['OM'] = 'Mittelfeld';
		$mainPositions['DM'] = 'Mittelfeld';
		$mainPositions['RM'] = 'Mittelfeld';
		$mainPositions['LS'] = 'Sturm';
		$mainPositions['MS'] = 'Sturm';
		$mainPositions['RS'] = 'Sturm';
		
		// create players for all positions
		foreach($positions as $mainPosition => $numberOfPlayers) {
			
			for ($playerNo = 1; $playerNo <= $numberOfPlayers; $playerNo++) {
				
				$playerAge = $age + self::_getRandomDeviationValue($ageDeviation);
				$time = strtotime('-' . $playerAge . ' years', $websoccer->getNowAsTimestamp());
				$birthday = date('Y-m-d', $time);
				
				$firstName = self::_getItemFromArray($firstNames);
				$lastName = self::_getItemFromArray($lastNames);
				self::_createPlayer($websoccer, $db, $teamId, $firstName, $lastName,
						$mainPositions[$mainPosition], $mainPosition, $strengths, $country, $playerAge, $birthday, $salary, $contractDuration, $maxDeviation);
			}

		}
		
	}
	
	private static function _getLines($fileName, $country) {
		$filePath = sprintf($fileName, $country);
		
		if (!file_exists($filePath)) {
			self::_throwException('generator_err_filedoesnotexist', $filePath);
		}
		
		$items = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!count($items)) {
			self::_throwException('generator_err_emptyfile', $filePath);
		}
		
		return $items;
	}
	
	private static function _getItemFromArray($items) {
		$itemsCount = count($items);
		if ($itemsCount) {
			return $items[mt_rand(0, $itemsCount - 1)];
		}
		
		return FALSE;
	}
	
	private static function _throwException($messageKey, $parameter = null) {
		$websoccer = WebSoccer::getInstance();
		$i18n = I18n::getInstance($websoccer->getConfig('supported_languages'));
		throw new Exception($i18n->getMessage($messageKey, $parameter));
	}
	
	private static function _createTeam($websoccer, $db, $league, $country, $cityName, $prefixes, $suffixes, $budget,
			$generateStadium, $stadiumNamePattern, $stadiumStands, $stadiumSeats, $stadiumStandsGrand, $stadiumSeatsGrand, $stadiumVip) {
		$teamName = $cityName;
		$shortName = strtoupper(substr($cityName, 0, 3));
		
		// prefix or suffix?
		if (rand(0, 1) && count($suffixes)) {
			$teamName .= ' ' . self::_getItemFromArray($suffixes);
		} else {
			$teamName = self::_getItemFromArray($prefixes) . ' ' . $teamName;
		}
		
		// stadium
		$stadiumId = 0;
		if ($generateStadium) {
			$stadiumName = sprintf($stadiumNamePattern, $cityName);
			
			$stadiumcolumns['name'] = $stadiumName;
			$stadiumcolumns['stadt'] = $cityName;
			$stadiumcolumns['land'] = $country;
			$stadiumcolumns['p_steh'] = $stadiumStands;
			$stadiumcolumns['p_sitz'] = $stadiumSeats;
			$stadiumcolumns['p_haupt_steh'] = $stadiumStandsGrand;
			$stadiumcolumns['p_haupt_sitz'] = $stadiumSeatsGrand;
			$stadiumcolumns['p_vip'] = $stadiumVip;
			
			$fromTable = $websoccer->getConfig('db_prefix') . '_stadion';
			
			$db->queryInsert($stadiumcolumns, $fromTable);
			
			// get generated ID
			$stadiumId = $db->getLastInsertedId();
		}
		
		$teamcolumns['name'] = $teamName;
		$teamcolumns['kurz'] = $shortName;
		$teamcolumns['liga_id'] = $league['id'];
		$teamcolumns['stadion_id'] = $stadiumId;
		$teamcolumns['finanz_budget'] = $budget;
		$teamcolumns['preis_stehen'] = $league['preis_steh'];
		$teamcolumns['preis_sitz'] = $league['preis_sitz'];
		$teamcolumns['preis_haupt_stehen'] = $league['preis_steh'];
		$teamcolumns['preis_haupt_sitze'] = $league['preis_sitz'];
		$teamcolumns['preis_vip'] = $league['preis_vip'];
		$teamcolumns['status'] = '1';
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_verein';
		$db->queryInsert($teamcolumns, $fromTable);
		
		echo '<p>' . $teamName . ' (' . $shortName . ')</p>';
	}
	
	private static function _createPlayer($websoccer, $db, $teamId, $firstName, $lastName, $position, $mainPosition, $strengths, 
			$country, $age, $birthday, $salary, $contractDuration, $maxDeviation) {
		
		$columns['vorname'] = $firstName;
		$columns['nachname'] = $lastName;
		$columns['geburtstag'] = $birthday;
		$columns['age'] = $age;
		$columns['position'] = $position;
		$columns['position_main'] = $mainPosition;
		$columns['nation'] = $country;
		$columns['w_staerke'] = max(1, min(100, $strengths['strength'] + self::_getRandomDeviationValue($maxDeviation)));
		$columns['w_technik'] = max(1, min(100, $strengths['technique'] + self::_getRandomDeviationValue($maxDeviation)));
		$columns['w_kondition'] = max(1, min(100, $strengths['stamina'] + self::_getRandomDeviationValue($maxDeviation)));
		$columns['w_frische'] = max(1, min(100, $strengths['freshness'] + self::_getRandomDeviationValue($maxDeviation)));
		$columns['w_zufriedenheit'] = max(1, min(100, $strengths['satisfaction'] + self::_getRandomDeviationValue($maxDeviation)));
		$columns['vertrag_gehalt'] = $salary;
		$columns['vertrag_spiele'] = $contractDuration;
		$columns['status'] = '1';
		
		if ($teamId) {
			$columns['verein_id'] = $teamId;
		} else {
			$columns['transfermarkt'] = '1';
			$columns['transfer_start'] = $websoccer->getNowAsTimestamp();
			$columns['transfer_ende'] = $columns['transfer_start'] + $websoccer->getConfig('transfermarket_duration_days') * 24 * 3600;
		}
		
		$fromTable = $websoccer->getConfig('db_prefix') . '_spieler';
		$db->queryInsert($columns, $fromTable);
	}
	
	private static function _getRandomDeviationValue($maxDeviation) {
		if ($maxDeviation <= 0) {
			return 0;
		}
		
		return mt_rand(0 - $maxDeviation, $maxDeviation);
	}
	
}
?>