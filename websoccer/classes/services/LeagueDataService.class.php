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
 * Data service for leagues.
 */
class LeagueDataService {
	
	/**
	 * Provides information about a single league.
	 * Query is cached.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $leagueId ID of league.
	 * @return array data about league or empty array if league could not be found.
	 */
	public static function getLeagueById(WebSoccer $websoccer, DbConnection $db, $leagueId) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_liga AS L";
	
		// where
		$whereCondition = "L.id = %d";
		$parameters = $leagueId;
	
		// select
		$columns["L.id"] = "league_id";
		$columns["L.name"] = "league_name";
		$columns["L.kurz"] = "league_short";
		$columns["L.land"] = "league_country";
	
		$leagueinfos = $db->queryCachedSelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$league = (isset($leagueinfos[0])) ? $leagueinfos[0] : array();
		
		return $league;
	}
	
	/**
	 * Provides leagues in a by country sorted order.
	 * Query is cached.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @return array list of leagues or empty array.
	 */
	public static function getLeaguesSortedByCountry(WebSoccer $websoccer, DbConnection $db) {
		$fromTable = $websoccer->getConfig("db_prefix") . "_liga AS L";
	
		// where
		$whereCondition = "1 ORDER BY league_country ASC, league_name ASC";
	
		// select
		$columns["L.id"] = "league_id";
		$columns["L.name"] = "league_name";
		$columns["L.kurz"] = "league_short";
		$columns["L.land"] = "league_country";
	
		return $db->queryCachedSelect($columns, $fromTable, $whereCondition);
	}
	
	/**
	 * Provide total number of leagues.
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @return int total number of leagues.
	 */
	public static function countTotalLeagues(WebSoccer $websoccer, DbConnection $db) {
		$result = $db->querySelect("COUNT(*) AS hits", $websoccer->getConfig("db_prefix") . "_liga",
				"1=1");
		$leagues = $result->fetch_array();
		$result->free();
	
		if (isset($leagues["hits"])) {
			return $leagues["hits"];
		}
	
		return 0;
	}
	
}
?>