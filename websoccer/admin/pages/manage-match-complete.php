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

echo "<h1>".  $i18n->getMessage("match_manage_complete") . "</h1>";

if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin["r_spiele"]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

echo "<p><a href=\"?site=manage&entity=match\" class=\"btn\">". $i18n->getMessage("back_label") . "</a></p>";

$matchId = (isset($_REQUEST["match"]) && is_numeric($_REQUEST["match"])) ? $_REQUEST["match"] : 0;
$match = MatchesDataService::getMatchById($website, $db, $matchId, FALSE, FALSE);
if (!count($match)) {
	throw new Exception("illegal match id");
}

if ($match["match_simulated"]) {
	throw new Exception($i18n->getMessage("match_manage_complete_alreadysimulated"));
}

// ******** action: complete
if ($action == "complete") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	// set goals and minutes played
	$statTable = $website->getConfig("db_prefix") . "_spiel_berechnung";
	$result = $db->querySelect("SUM(tore) AS goals, MAX(minuten_gespielt) AS minutes", $statTable, "spiel_id = %d AND team_id = %d", 
			array($matchId, $match["match_home_id"]));
	$homeStat = $result->fetch_array();
	$result->free();
	
	$result = $db->querySelect("SUM(tore) AS goals", $statTable, "spiel_id = %d AND team_id = %d",
			array($matchId, $match["match_guest_id"]));
	$guestStat = $result->fetch_array();
	$result->free();
	
	$db->queryUpdate(array(
			"minutes" => $homeStat["minutes"],
			"home_tore" => $homeStat["goals"],
			"gast_tore" => $guestStat["goals"],
			"berechnet" => "1"
			), $website->getConfig("db_prefix") . "_spiel", "id = %d", $matchId);
	
	// create internal model
	$fromTable = $website->getConfig("db_prefix") ."_spiel AS M";
	$fromTable .= " INNER JOIN " . $website->getConfig("db_prefix") ."_verein AS HOME_T ON HOME_T.id = M.home_verein";
	$fromTable .= " INNER JOIN " . $website->getConfig("db_prefix") ."_verein AS GUEST_T ON GUEST_T.id = M.gast_verein";
	
	$columns = array();
	$columns["M.id"] = "match_id";
	$columns["M.spieltyp"] = "type";
	$columns["M.home_verein"] = "home_id";
	$columns["M.gast_verein"] = "guest_id";
	$columns["M.minutes"] = "minutes";
	$columns["M.soldout"] = "soldout";
	$columns["M.elfmeter"] = "penaltyshooting";
	$columns["M.pokalname"] = "cup_name";
	$columns["M.pokalrunde"] = "cup_roundname";
	$columns["M.pokalgruppe"] = "cup_groupname";
	
	$columns["M.player_with_ball"] = "player_with_ball";
	$columns["M.prev_player_with_ball"] = "prev_player_with_ball";
	$columns["M.home_tore"] = "home_goals";
	$columns["M.gast_tore"] = "guest_goals";
	
	$columns["M.home_offensive"] = "home_offensive";
	$columns["M.home_setup"] = "home_setup";
	$columns["M.home_noformation"] = "home_noformation";
	$columns["M.home_longpasses"] = "home_longpasses";
	$columns["M.home_counterattacks"] = "home_counterattacks";
	$columns["M.gast_offensive"] = "guest_offensive";
	$columns["M.guest_noformation"] = "guest_noformation";
	$columns["M.gast_setup"] = "guest_setup";
	$columns["M.gast_longpasses"] = "guest_longpasses";
	$columns["M.gast_counterattacks"] = "guest_counterattacks";
	
	$columns["HOME_T.name"] = "home_name";
	$columns["HOME_T.nationalteam"] = "home_nationalteam";
	$columns["GUEST_T.nationalteam"] = "guest_nationalteam";
	$columns["GUEST_T.name"] = "guest_name";
	
	// substitutions
	for ($subNo = 1; $subNo <= 3; $subNo++) {
		$columns["M.home_w" . $subNo . "_raus"] = "home_sub_" . $subNo . "_out";
		$columns["M.home_w" . $subNo . "_rein"] = "home_sub_" . $subNo . "_in";
		$columns["M.home_w" . $subNo . "_minute"] = "home_sub_" . $subNo . "_minute";
		$columns["M.home_w" . $subNo . "_condition"] = "home_sub_" . $subNo . "_condition";
			
		$columns["M.gast_w" . $subNo . "_raus"] = "guest_sub_" . $subNo . "_out";
		$columns["M.gast_w" . $subNo . "_rein"] = "guest_sub_" . $subNo . "_in";
		$columns["M.gast_w" . $subNo . "_minute"] = "guest_sub_" . $subNo . "_minute";
		$columns["M.gast_w" . $subNo . "_condition"] = "guest_sub_" . $subNo . "_condition";
	}
	
	$result = $db->querySelect($columns, $fromTable, "M.id = %d", $matchId);
	$matchinfo = $result->fetch_array();
	$result->free();
	
	// init default simulation strategry in order to include dependend constants. Yeah, refactor this once having to much time...
	$dummyVar = new DefaultSimulationStrategy($website);
	$matchModel = SimulationStateHelper::loadMatchState($website, $db, $matchinfo);
	
	// compute audience
	if ($website->getRequestParameter("computetickets")) {
		SimulationAudienceCalculator::computeAndSaveAudience($website, $db, $matchModel);
	}
	
	if ($matchinfo["type"] == "Pokalspiel") {
		SimulationCupMatchHelper::checkIfExtensionIsRequired($website, $db, $matchModel);
	}
	
	// complete match
	$observer = new DataUpdateSimulatorObserver($website, $db);
	$observer->onMatchCompleted($matchModel);
	
	// show success message
	echo createSuccessMessage($i18n->getMessage("match_manage_complete_success"), "");
	
}

echo "<h3><a href=\"". $website->getInternalUrl("team", "id=" . $match["match_home_id"]) . "\" target=\"_blank\">". escapeOutput($match["match_home_name"]) . "</a> - <a href=\"". $website->getInternalUrl("team", "id=" . $match["match_guest_id"]) . "\" target=\"_blank\">". escapeOutput($match["match_guest_name"]) . "</a></h3>";

echo "<div class=\"well\">". $i18n->getMessage("match_manage_complete_intro") . "</div>";

echo "<form action=\"". $_SERVER['PHP_SELF'] . "\" method=\"post\" class=\"form-horizontal\">";
echo "<input type=\"hidden\" name=\"site\" value=\"$site\"/>";
echo "<input type=\"hidden\" name=\"action\" value=\"complete\"/>";
echo "<input type=\"hidden\" name=\"match\" value=\"$matchId\"/>";
echo FormBuilder::createFormGroup($i18n, "computetickets", array("type" => "boolean",
		"value" => "1"), "1", "match_manage_complete_");
echo "<div class=\"form-actions\">";
echo "<button type=\"submit\" class=\"btn btn-primary\">". $i18n->getMessage("match_manage_complete_button") . "</button>";
echo "</div></form>";

?>