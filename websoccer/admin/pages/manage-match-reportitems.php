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

echo "<h1>".  $i18n->getMessage("match_manage_reportitems") . "</h1>";

if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin["r_spiele"]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

echo "<p><a href=\"?site=manage&entity=match\" class=\"btn\">". $i18n->getMessage("back_label") . "</a></p>";

$matchId = (isset($_REQUEST["match"]) && is_numeric($_REQUEST["match"])) ? $_REQUEST["match"] : 0;
$match = MatchesDataService::getMatchById($website, $db, $matchId, FALSE, FALSE);
if (!count($match)) {
	throw new Exception("illegal match id");
}

// ******** action: delete player from match
if ($action == "delete") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	$itemId = (int) $_REQUEST["itemid"];
	$db->queryDelete($website->getConfig("db_prefix") . "_matchreport", "id = %d", array($itemId));
	echo createSuccessMessage($i18n->getMessage("manage_success_delete"), "");
}
// ******** create match report item
elseif ($action == "create") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	$homeActive = ($match["match_home_id"] == $_POST["team_id"]) ? "1" : "0";
	$message_id = (int) $_POST["message_id"];
	$minute = (int) $_POST["minute"];
	$playerNames = $_POST["playernames"];
	$goals = $_POST["intermediateresult"];
	
	if ($message_id && $minute) {
		
		$db->queryInsert(array(
				"match_id" => $matchId,
				"message_id" => $message_id,
				"active_home" => $homeActive,
				"minute" => $minute,
				"goals" => $goals,
				"playernames" => $playerNames
				), $website->getConfig("db_prefix") . "_matchreport");
	}
}

// ******** form for adding new item
echo "<form action=\"". $_SERVER['PHP_SELF'] . "\" class=\"form-horizontal\" method=\"post\">";
echo "<input type=\"hidden\" name=\"action\" value=\"create\">";
echo "<input type=\"hidden\" name=\"site\" value=\"$site\">";
echo "<input type=\"hidden\" name=\"match\" value=\"$matchId\">";

echo "<fieldset><legend>". $i18n->getMessage("match_manage_createmessage_title") ."</legend>";

echo "<div class=\"control-group\">";
echo "<label class=\"control-label\" for=\"team_id\">". $i18n->getMessage("entity_player_verein_id") . "</label>";
echo "<div class=\"controls\">";
echo "<select name=\"team_id\" id=\"team_id\">";
echo "<option value=\"". $match["match_home_id"] . "\">". escapeOutput($match["match_home_name"]) . "</option>";
echo "<option value=\"". $match["match_guest_id"] . "\">". escapeOutput($match["match_guest_name"]) . "</option>";
echo "</select>";
echo "</div>";
echo "</div>";

echo FormBuilder::createFormGroup($i18n, "message_id", array("type" => "foreign_key", 
		"jointable" => "spiel_text", 
		"entity" => "matchtext",
		"labelcolumns" => "aktion,nachricht"), "", "match_manage_reportmsg_");
echo FormBuilder::createFormGroup($i18n, "playernames", array("type" => "text"), "", "match_manage_reportmsg_");
echo FormBuilder::createFormGroup($i18n, "minute", array("type" => "number"), "", "match_manage_reportmsg_");
echo FormBuilder::createFormGroup($i18n, "intermediateresult", array("type" => "text"), "", "match_manage_reportmsg_");

echo "</fieldset>";
echo "<div class=\"form-actions\">";
echo "<button type=\"submit\" class=\"btn btn-primary\">". $i18n->getMessage("button_save") . "</button>";
echo "</div></form>";

// ******** list items

$reportItems = MatchesDataService::getMatchReportMessages($website, $db, $i18n, $matchId);
	
// no items
if (!count($reportItems)) {
	echo createInfoMessage("", $i18n->getMessage("match_manage_reportitems_noitems"));
	
	// list items
} else {
	echo "<table class=\"table table-bordered table-striped table-hover\">";
	echo "<thead>";
	echo "<tr>";
	echo "<th>". $i18n->getMessage("match_manage_reportmsg_minute") . "</th>";
	echo "<th>". $i18n->getMessage("entity_matchtext_aktion") . "</th>";
	echo "<th>". $i18n->getMessage("entity_matchtext_nachricht") . "</th>";
	echo "<th>". $i18n->getMessage("match_manage_reportmsg_playernames") . "</th>";
	echo "<th>". $i18n->getMessage("match_manage_reportmsg_intermediateresult") . "</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	
	$homeTeam = escapeOutput($match["match_home_name"]);
	$guestTeam = escapeOutput($match["match_guest_name"]);
	
	foreach ($reportItems as $reportItem) {
		echo "<tr>";
		
		echo "<td><a href=\"?site=$site&action=delete&match=$matchId&itemid=". $reportItem["report_id"] . "\" title=\"". $i18n->getMessage("manage_delete") . "\" class=\"deleteLink\"><i class=\"icon-trash\"></i></a> ". $reportItem["minute"] . "</td>";
		echo "<td><small>";
		if ($reportItem["active_home"]) {
			echo $homeTeam;
		} else {
			echo $guestTeam;
		}
		echo "</small><br>";
		echo $i18n->getMessage("option_" . $reportItem["type"]);
		echo "</td>";
		echo "<td>". $reportItem["message"] . "</td>";
		echo "<td>". $reportItem["playerNames"] . "</td>";
		echo "<td>". $reportItem["goals"] . "</td>";
		echo "</tr>";
	}
	
	echo "</tbody>";
	echo "</table>";
}

?>