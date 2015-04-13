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
define("INACTIVITY_PER_DAY_LOGIN", 0.45);
define("INACTIVITY_PER_DAY_TRANSFERS", 0.1);
define("INACTIVITY_PER_DAY_TACTICS", 0.2);
define("INACTIVITY_PER_CONTRACTEXTENSION", 5);

/**
 * Data service for user inactivity logging
 */
class UserInactivityDataService {
	
	public static function getUserInactivity(WebSoccer $websoccer, DbConnection $db, $userId) {
		
		$columns["id"] = "id";
		$columns["login"] = "login";
		$columns["login_check"] = "login_check";
		$columns["aufstellung"] = "tactics";
		$columns["transfer"] = "transfer";
		$columns["transfer_check"] = "transfer_check";
		$columns["vertragsauslauf"] = "contractextensions";
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_user_inactivity";
		
		$whereCondition = "user_id = %d";
		$parameters = $userId;
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters, 1);
		$inactivity = $result->fetch_array();
		$result->free();
		
		// create new entry
		if (!$inactivity) {
			$newcolumns["user_id"] = $userId;
			$db->queryInsert($newcolumns, $fromTable);
			return self::getUserInactivity($websoccer, $db, $userId);
		}
		
		return $inactivity;
	}
	
	public static function computeUserInactivity(WebSoccer $websoccer, DbConnection $db, $userId) {
		$inactivity = self::getUserInactivity($websoccer, $db, $userId);
		
		$now = $websoccer->getNowAsTimestamp();
		
		$checkBoundary = $now - 24 * 3600;
		
		$updatecolumns = array();
		
		$user = UsersDataService::getUserById($websoccer, $db, $userId);
		
		// compute login-activity
		if ($inactivity["login_check"] < $checkBoundary) {
			$inactiveDays = round(($now - $user["lastonline"]) / (24 * 3600));
			$updatecolumns["login"] = min(100, round($inactiveDays * INACTIVITY_PER_DAY_LOGIN));
			$updatecolumns["login_check"] = $now;
			
			// update tactics activity
			$formationTable = $websoccer->getConfig("db_prefix") . "_aufstellung AS F";
			$formationTable .= " INNER JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS T ON T.id = F.verein_id";
			$result = $db->querySelect("F.datum AS date", $formationTable, "T.user_id = %d", $userId);
			$formation = $result->fetch_array();
			$result->free();
			if ($formation) {
				$inactiveDays = round(($now - $formation["date"]) / (24 * 3600));
				$updatecolumns["aufstellung"] = min(100, round($inactiveDays * INACTIVITY_PER_DAY_TACTICS));
			}
		}
		
		// compute transfers-activity (check user's bids)
		if ($inactivity["transfer_check"] < $checkBoundary) {
			$bid = TransfermarketDataService::getLatestBidOfUser($websoccer, $db, $userId);
			$transferBenchmark = $user["registration_date"];
			if ($bid) {
				$transferBenchmark = $bid["date"];
			}
				
			$inactiveDays = round(($now - $transferBenchmark) / (24 * 3600));
			$updatecolumns["transfer"] = min(100, round($inactiveDays * INACTIVITY_PER_DAY_TRANSFERS));
			$updatecolumns["transfer_check"] = $now;
		}
		
		// update
		if (count($updatecolumns)) {
			$fromTable = $websoccer->getConfig("db_prefix") . "_user_inactivity";
			$db->queryUpdate($updatecolumns, $fromTable, "id = %d", $inactivity["id"]);
		}
		
	}
	
	public static function resetContractExtensionField($websoccer, $db, $userId) {
		$inactivity = self::getUserInactivity($websoccer, $db, $userId);
		
		$updatecolumns["vertragsauslauf"] = 0;
		$fromTable = $websoccer->getConfig("db_prefix") . "_user_inactivity";
		$db->queryUpdate($updatecolumns, $fromTable, "id = %d", $inactivity["id"]);
	}
	
	public static function increaseContractExtensionField($websoccer, $db, $userId) {
		$inactivity = self::getUserInactivity($websoccer, $db, $userId);
	
		$updatecolumns["vertragsauslauf"] = min(100, $inactivity["contractextensions"] + INACTIVITY_PER_CONTRACTEXTENSION);
		$fromTable = $websoccer->getConfig("db_prefix") . "_user_inactivity";
		$db->queryUpdate($updatecolumns, $fromTable, "id = %d", $inactivity["id"]);
	}
	
}
?>