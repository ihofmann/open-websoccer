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
 * Calculates sold tickets for a given match.
 * 
 * @author Ingo Hofmann
 */
class SimulationAudienceCalculator {
	
	/**
	 * Computes and stores the audience, including crediting the sales revenue.
	 * Considers following factors:
	 * - Fan popularity
	 * - Ticket prices (compared to league average prices, which are set by the admin)
	 * - bonus if the match is attractive. It is attractive if it is a cup match or if teams are neighbors in the standings.
	 * 
	 * @param WebSoccer $websoccer request context.
	 * @param DbConnection $db database connection.
	 * @param SimulationMatch $match Simulation match for that the audience shall be computed.
	 */
	public static function computeAndSaveAudience(WebSoccer $websoccer, DbConnection $db, SimulationMatch $match) {
		// get stadium, user and team info
		$homeInfo = self::getHomeInfo($websoccer, $db, $match->homeTeam->id);
		if (!$homeInfo) {
			return;
		}
			
		// is match in particular attractive?
		$isAttractiveMatch = FALSE;
		if ($match->type == 'Pokalspiel') {
			$isAttractiveMatch = TRUE;
		} else if ($match->type == 'Ligaspiel') {
			// consider difference between points
			$tcolumns = 'sa_punkte';
			$fromTable = $websoccer->getConfig('db_prefix') . '_verein';
			$whereCondition = 'id = %d';
			
			$result = $db->querySelect($tcolumns, $fromTable, $whereCondition, $match->homeTeam->id);
			$home = $result->fetch_array();
			$result->free();
			
			$result = $db->querySelect($tcolumns, $fromTable, $whereCondition, $match->guestTeam->id);
			$guest = $result->fetch_array();
			$result->free();
			
			if (abs($home['sa_punkte'] - $guest['sa_punkte']) <= 9) {
				$isAttractiveMatch = TRUE;
			}
		}
		
		// consider stadium extras
		$maintenanceInfluence = $homeInfo['level_videowall'] * $websoccer->getConfig('stadium_videowall_effect');
		$maintenanceInfluenceSeats = (5 - $homeInfo['level_seatsquality']) * $websoccer->getConfig('stadium_seatsquality_effect');
		$maintenanceInfluenceVip = (5 - $homeInfo['level_vipquality']) * $websoccer->getConfig('stadium_vipquality_effect');
		
		// compute sold tickets
		$rateStands = self::computeRate($homeInfo['avg_price_stands'], 
											$homeInfo['avg_sales_stands'], 
											$homeInfo['price_stands'], 
											$homeInfo['popularity'], 
											$isAttractiveMatch,
											$maintenanceInfluence);
		
		$rateSeats = self::computeRate($homeInfo['avg_price_seats'],
											$homeInfo['avg_sales_seats'],
											$homeInfo['price_seats'],
											$homeInfo['popularity'],
											$isAttractiveMatch,
											$maintenanceInfluence - $maintenanceInfluenceSeats);
		
		$rateStandsGrand = self::computeRate($homeInfo['avg_price_stands'] * 1.2,
											$homeInfo['avg_sales_stands_grand'],
											$homeInfo['price_stands_grand'],
											$homeInfo['popularity'],
											$isAttractiveMatch,
											$maintenanceInfluence);
		
		$rateSeatsGrand = self::computeRate($homeInfo['avg_price_seats'] * 1.2,
											$homeInfo['avg_sales_seats_grand'],
											$homeInfo['price_seats_grand'],
											$homeInfo['popularity'],
											$isAttractiveMatch,
											$maintenanceInfluence - $maintenanceInfluenceSeats);
		
		$rateVip = self::computeRate($homeInfo['avg_price_vip'],
											$homeInfo['avg_sales_vip'],
											$homeInfo['price_vip'],
											$homeInfo['popularity'],
											$isAttractiveMatch,
											$maintenanceInfluence - $maintenanceInfluenceVip);
		
		// call plug-ins
		$event = new TicketsComputedEvent($websoccer, $db, I18n::getInstance($websoccer->getConfig('supported_languages')), 
				$match, $homeInfo['stadium_id'], $rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip);
		PluginMediator::dispatchEvent($event);
		
		// is sold out?
		if ($rateStands == 1 && $rateSeats == 1 && $rateStandsGrand == 1 && $rateSeatsGrand == 1 && $rateVip == 1) {
			$match->isSoldOut = TRUE;
		}
		
		$tickets_stands = min(1, max(0, $rateStands)) * $homeInfo['places_stands'];
		$tickets_seats = min(1, max(0, $rateSeats)) * $homeInfo['places_seats'];
		$tickets_stands_grand = min(1, max(0, $rateStandsGrand)) * $homeInfo['places_stands_grand'];
		$tickets_seats_grand = min(1, max(0, $rateSeatsGrand)) * $homeInfo['places_seats_grand'];
		$tickets_vip = min(1, max(0, $rateVip)) * $homeInfo['places_vip'];
		
		// update team statistic
		$columns['last_steh'] = $tickets_stands;
		$columns['last_sitz'] = $tickets_seats;
		$columns['last_haupt_steh'] = $tickets_stands_grand;
		$columns['last_haupt_sitz'] = $tickets_seats_grand;
		$columns['last_vip'] = $tickets_vip;
		$fromTable = $websoccer->getConfig('db_prefix') . '_verein';
		$whereCondition = 'id = %d';
		$db->queryUpdate($columns, $fromTable, $whereCondition, $match->homeTeam->id);
		
		// update match field
		$mcolumns['zuschauer'] = $tickets_stands + $tickets_seats + $tickets_stands_grand + $tickets_seats_grand + $tickets_vip;
		$fromTable = $websoccer->getConfig('db_prefix') . '_spiel';
		$db->queryUpdate($mcolumns, $fromTable, $whereCondition, $match->id);
		
		// compute and credit income
		$revenue = $tickets_stands * $homeInfo['price_stands'];
		$revenue += $tickets_seats * $homeInfo['price_seats'];
		$revenue += $tickets_stands_grand * $homeInfo['price_stands_grand'];
		$revenue += $tickets_seats_grand * $homeInfo['price_seats_grand'];
		$revenue += $tickets_vip * $homeInfo['price_vip'];
		
		BankAccountDataService::creditAmount($websoccer, $db, $match->homeTeam->id,
				$revenue,
				'match_ticketrevenue_subject',
				'match_ticketrevenue_sender');
		
		self::weakenPlayersDueToGrassQuality($websoccer, $homeInfo, $match);
		self::updateMaintenanceStatus($websoccer, $db, $homeInfo);
	}	
	
	private static function getHomeInfo($websoccer, $db, $teamId) {
		$fromTable = $websoccer->getConfig('db_prefix') . '_verein AS T';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_stadion AS S ON S.id = T.stadion_id';
		$fromTable .= ' INNER JOIN ' . $websoccer->getConfig('db_prefix') . '_liga AS L ON L.id = T.liga_id';
		$fromTable .= ' LEFT JOIN ' . $websoccer->getConfig('db_prefix') . '_user AS U ON U.id = T.user_id';
		$whereCondition = 'T.id = %d';
		
		$columns['S.id'] = 'stadium_id';
		$columns['S.p_steh'] = 'places_stands';
		$columns['S.p_sitz'] = 'places_seats';
		$columns['S.p_haupt_steh'] = 'places_stands_grand';
		$columns['S.p_haupt_sitz'] = 'places_seats_grand';
		$columns['S.p_vip'] = 'places_vip';
		
		$columns['S.level_pitch'] = 'level_pitch';
		$columns['S.level_videowall'] = 'level_videowall';
		$columns['S.level_seatsquality'] = 'level_seatsquality';
		$columns['S.level_vipquality'] = 'level_vipquality';
		
		$columns['S.maintenance_pitch'] = 'maintenance_pitch';
		$columns['S.maintenance_videowall'] = 'maintenance_videowall';
		$columns['S.maintenance_seatsquality'] = 'maintenance_seatsquality';
		$columns['S.maintenance_vipquality'] = 'maintenance_vipquality';
		
		$columns['U.fanbeliebtheit'] = 'popularity';
		
		$columns['T.preis_stehen'] = 'price_stands';
		$columns['T.preis_sitz'] = 'price_seats';
		$columns['T.preis_haupt_stehen'] = 'price_stands_grand';
		$columns['T.preis_haupt_sitze'] = 'price_seats_grand';
		$columns['T.preis_vip'] = 'price_vip';
		
		$columns['L.p_steh'] = 'avg_sales_stands';
		$columns['L.p_sitz'] = 'avg_sales_seats';
		$columns['L.p_haupt_steh'] = 'avg_sales_stands_grand';
		$columns['L.p_haupt_sitz'] = 'avg_sales_seats_grand';
		$columns['L.p_vip'] = 'avg_sales_vip';
		
		$columns['L.preis_steh'] = 'avg_price_stands';
		$columns['L.preis_sitz'] = 'avg_price_seats';
		$columns['L.preis_vip'] = 'avg_price_vip';
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $teamId);
		$record = $result->fetch_array();
		$result->free();
		
		return $record;
	}
	
	private static function computeRate($avgPrice, $avgSales, $actualPrice, $fanpopularity, $isAttractiveMatch, $maintenanceInfluence) {
		$rate = 100 - pow((10 / (2.5 * $avgPrice) * $actualPrice), 2);
		
		// consider average sales
		$deviation = $avgSales - (100 - pow((10 / (2.5 * $avgPrice) * $avgPrice), 2));
		$rate = $rate + $deviation;
		
		// consider fan popularity (-10 up to +10%)
		if ($rate > 0) {
			$rate = $rate - 10 + 1/5 * $fanpopularity;
		}
		
		if ($isAttractiveMatch) {
			$rate = $rate * 1.1;
		}
		
		// stadium extras
		if ($rate > 0) {
			$rate = $rate + $maintenanceInfluence;
		}
		
		return min(100, max(0, $rate)) / 100;
	}
	
	private static function updateMaintenanceStatus(WebSoccer $websoccer, DbConnection $db, $homeInfo) {
		
		$columns = array(
				'maintenance_pitch' => $homeInfo['maintenance_pitch'] - 1,
				'maintenance_videowall' => $homeInfo['maintenance_videowall'] - 1,
				'maintenance_seatsquality' => $homeInfo['maintenance_seatsquality'] - 1,
				'maintenance_vipquality' => $homeInfo['maintenance_vipquality'] - 1
				);
		
		// check if maintenance interval expired
		$types = array('pitch', 'videowall', 'seatsquality', 'vipquality');
		foreach ($types as $type) {
			if ($columns['maintenance_' . $type] <= 0) {
				$columns['maintenance_' . $type] = $websoccer->getConfig('stadium_maintenanceinterval_' . $type);
				$columns['level_' . $type] = max(0, $homeInfo['level_' . $type] - 1);
			}
		}
		
		$db->queryUpdate($columns, $websoccer->getConfig('db_prefix') . '_stadion', 'id = %d', $homeInfo['stadium_id']);
	}
	
	private static function weakenPlayersDueToGrassQuality(WebSoccer $websoccer, $homeInfo, SimulationMatch $match) {
		
		$strengthChange = (5 - $homeInfo['level_pitch']) * $websoccer->getConfig('stadium_pitch_effect');
		if ($strengthChange && $match->type != 'Freundschaft') {
			$playersAndPositions = $match->homeTeam->positionsAndPlayers;
			foreach ($playersAndPositions as $positions => $players) {
				foreach ($players as $player) {
					$player->strengthTech = max(1, $player->strengthTech - $strengthChange);
				}
			}
		}
	}
}
?>