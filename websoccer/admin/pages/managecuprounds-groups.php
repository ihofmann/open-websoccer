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

$mainTitle = $i18n->getMessage("managecuprounds_groups_navlabel");

echo "<h1>$mainTitle</h1>";

if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin["r_spiele"]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

$roundid = (isset($_REQUEST["round"]) && is_numeric($_REQUEST["round"])) ? $_REQUEST["round"] : 0;

$result = $db->querySelect("R.id AS round_id,R.name AS round_name,firstround_date,secondround_date,C.id AS cup_id,C.name as cup_name", 
		$website->getConfig("db_prefix") . "_cup_round AS R INNER JOIN " . $website->getConfig("db_prefix") . "_cup AS C ON C.id = R.cup_id", 
		"R.id = %d", $roundid);
$round = $result->fetch_array();
$result->free();
if (!isset($round["round_name"])) {
	throw new Exception("illegal round id");
}

echo "<h2>". $i18n->getMessage("entity_cup") . " - " . escapeOutput($round["round_name"]) . "</h2>";

echo "<p><a href=\"?site=managecuprounds&cup=". $round["cup_id"] . "\" class=\"btn\">" . $i18n->getMessage("managecuprounds_groups_back") ."</a></p>";

// get teams for team selection
$result = $db->querySelect("T.id AS team_id,T.name AS team_name,L.name AS league_name,L.land AS league_country",
		$website->getConfig("db_prefix") . "_verein AS T INNER JOIN " . $website->getConfig("db_prefix") . "_liga AS L ON L.id = T.liga_id",
		"1=1 ORDER BY team_name ASC");
$teams = array();
while ($team = $result->fetch_array()) {
	$teams[] = $team;
}
$result->free();

// configure create group form
$formFields = array();
$formFields["name"] = array("type" => "text", "value" => "", "required" => "true");

// configure generate schedule form
$generateFormFields = array();
$generateFormFields["firstmatchday"] = array("type" => "timestamp", "value" => "", "required" => "true");
$generateFormFields["rounds"] = array("type" => "number", "value" => "1", "required" => "true");
$generateFormFields["timebreak"] = array("type" => "number", "value" => 5, "required" => "true");

$showEditForm = FALSE;

// Actions
if ($action == "create") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}

	try {

		// validate fields
		foreach ($formFields as $fieldId => $fieldInfo) {
				
			$fieldValue = (isset($_POST[$fieldId])) ? $_POST[$fieldId] : "";
				
			FormBuilder::validateField($i18n, $fieldId, $fieldInfo, $fieldValue, "managecuprounds_group_label_");
		}
		
		$teamIds = $_POST["teams"];
		$inserTable = $website->getConfig("db_prefix") . "_cup_round_group";

		// save
		foreach($teamIds as $teamId) {
			$columns = array();
			$columns["cup_round_id"] = $roundid;
			$columns["team_id"] = $teamId;
			$columns["name"] = $_POST["name"];
	
			$db->queryInsert($columns, $inserTable);
		}

	} catch (Exception $e) {
		echo createErrorMessage($i18n->getMessage("subpage_error_alertbox_title") , $e->getMessage());
	}
	
	echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");

	// Action: delete
} elseif ($action == "delete") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}

	$db->queryDelete($website->getConfig("db_prefix") . "_cup_round_group", "cup_round_id = %d AND name = '%s'", array($roundid, $_GET["group"]));

	echo createSuccessMessage($i18n->getMessage("manage_success_delete"), "");
}else if ($action == "edit") {
	$showEditForm = TRUE;
	
} else if ($action == "editsave") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	if (isset($_REQUEST["groupname"]) && strlen(trim($_REQUEST["groupname"]))) {
		
		$columns = array("name" => $_REQUEST["groupname"]);
		
		$db->queryUpdate($columns, $website->getConfig("db_prefix") . "_cup_round_group", "cup_round_id = %d AND name = '%s'", 
				array($roundid, $_REQUEST["group"]));
		
		$db->queryUpdate(array("groupname" => $_REQUEST["groupname"]), $website->getConfig("db_prefix") . "_cup_round_group_next", "cup_round_id = %d AND groupname = '%s'",
				array($roundid, $_REQUEST["group"]));
		
		$db->queryUpdate(array("pokalgruppe" => $_REQUEST["groupname"]), $website->getConfig("db_prefix") . "_spiel", "pokalname = '%s' AND pokalrunde = '%s' AND pokalgruppe = '%s'",
				array($round["cup_name"], $round["round_name"], $_REQUEST["group"]));
		
		echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");
	}
	
} else if ($action == "deletegroupassignment") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	$db->queryDelete($website->getConfig("db_prefix") . "_cup_round_group", "cup_round_id = %d AND name = '%s' AND team_id = %d", array($roundid, $_GET["group"], $_GET["teamid"]));
	
	echo createSuccessMessage($i18n->getMessage("manage_success_delete"), "");
} else if ($action == "addteam") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	$columns = array();
	$columns["cup_round_id"] = $roundid;
	$columns["team_id"] = $_POST["teamid"];
	$columns["name"] = $_POST["group"];
	
	$db->queryInsert($columns, $website->getConfig("db_prefix") . "_cup_round_group");
	
	echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");
} else if ($action == "saveranks") {
	if ($admin["r_demo"]) {
		throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
	}
	
	$groupName = $_REQUEST["group"];
	
	$dbTable = $website->getConfig("db_prefix") . "_cup_round_group_next";
	
	// get existing rank configurations
	$result = $db->querySelect("*", $dbTable, "cup_round_id = %d AND groupname = '%s'", array($roundid, $groupName));
	$ranks = array();
	while ($rank = $result->fetch_array()) {
		$ranks["" . $rank["rank"]] = $rank;
	}
	$result->free();
	
	// get number of teams in this group
	$result = $db->querySelect("COUNT(*) AS teams", $website->getConfig("db_prefix") . "_cup_round_group", "cup_round_id = %d AND name = '%s'", array($roundid, $groupName));
	$hits = $result->fetch_array();
	$result->free();
	
	$noOfTeams = ($hits["teams"]) ? $hits["teams"] : 0;
	
	for ($groupRank = 1; $groupRank <= $noOfTeams; $groupRank++) {
		
		// delete old ranking config, if now no value exists
		if (!isset($_REQUEST["rank_" . $groupRank]) || !$_REQUEST["rank_" . $groupRank]) {
			if (isset($ranks["" . $groupRank])) {
				$db->queryDelete($dbTable, "cup_round_id = %d AND groupname = '%s' AND rank = %d", array($roundid, $groupName, $groupRank));
			}
		} else if($_REQUEST["rank_" . $groupRank]) {
			
			$columns = array();
			$columns["cup_round_id"] = $roundid;
			$columns["groupname"] = $groupName;
			$columns["rank"] = $groupRank;
			$columns["target_cup_round_id"] = $_REQUEST["rank_" . $groupRank];
			
			// update
			if (isset($ranks["" . $groupRank])) {
				$db->queryUpdate($columns, $dbTable, "cup_round_id = %d AND groupname = '%s' AND rank = %d", array($roundid, $groupName, $groupRank));
			// insert
			} else {
				$db->queryInsert($columns, $dbTable);
			}
			
		}
	}
	
	echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");
}

// query existing groups
$columns = "G.name AS group_name, C.name AS team_name, C.id AS team_id";
$fromTable = $website->getConfig("db_prefix") . "_cup_round_group AS G";
$fromTable .= " INNER JOIN " . $website->getConfig("db_prefix") . "_verein AS C ON C.id = G.team_id";

$whereCondition = "G.cup_round_id = %d ORDER BY G.name ASC, C.name ASC";
$result = $db->querySelect($columns, $fromTable, $whereCondition, $roundid);

$groups = array();
while ($group = $result->fetch_array()) {
	$groups[$group["group_name"]][] = $group;
}
$result->free();

// list groups
if (count($groups)) {
	
	// retrieve other cup rounds
	$result = $db->querySelect("*", $website->getConfig("db_prefix") . "_cup_round", "cup_id = %d AND id != %d ORDER BY firstround_date ASC", array($round["cup_id"], $round["round_id"]));
	$rounds = array();
	while ($roundItem = $result->fetch_array()) {
		$rounds[] = $roundItem;
	}
	$result->free();
	
	// retrieve rank configurations
	$result = $db->querySelect("*", $website->getConfig("db_prefix") . "_cup_round_group_next", "cup_round_id = %d", array($roundid));
	$rankConfigs = array();
	while ($rankConfig = $result->fetch_array()) {
		$rankConfigs[$rankConfig["groupname"]][$rankConfig["rank"]] = $rankConfig["target_cup_round_id"];
	}
	$result->free();
	
	?>
	
	<table class="table table-bordered">
		<colgroup>
			<col>
			<col>
			<col>
			<col style="width: 20px">
			<col style="width: 20px">
		</colgroup>
		<thead>
			<tr>
				<th><?php echo $i18n->getMessage("managecuprounds_group_label_name"); ?></th>
				<th><?php echo $i18n->getMessage("managecuprounds_group_label_teams"); ?></th>
				<th><?php echo $i18n->getMessage("managecuprounds_groups_nextrounds"); ?></th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php 
		foreach($groups as $groupName => $groupItems) {
			echo "<tr>";
			
			echo "<td>";
			if ($showEditForm && $_REQUEST["group"] == $groupName) {
				$nameValue = (isset($_REQUEST["groupname"])) ? $_REQUEST["groupname"] : $groupName;
				?>
				<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-inline">
					<input type="hidden" name="action" value="editsave">
					<input type="hidden" name="site" value="<?php echo $site; ?>">
					<input type="hidden" name="round" value="<?php echo $roundid; ?>">
					<input type="hidden" name="group" value="<?php echo escapeOutput($groupName); ?>">
					
					<input id="groupname" name="groupname" type="text" value="<?php echo escapeOutput($nameValue) ?>">
					<input type="submit" class="btn" value="<?php echo $i18n->getMessage("button_save"); ?>">
				</form>
				<?php
			} else {
				echo escapeOutput($groupName);
			}
			
			echo "</td>";
			
			echo "<td><ul>";
			$noOfTeams = 0;
			foreach ($groupItems as $groupItem) {
				echo "<li>" . escapeOutput($groupItem["team_name"]) . " <a class=\"deleteLink\" href=\"?site=". $site . "&round=". $roundid . "&group=". urlencode(escapeOutput($groupName)) . "&action=deletegroupassignment&teamid=". $groupItem["team_id"] . "\" title=\"". $i18n->getMessage("manage_delete") . "\"><i class=\"icon-remove-sign\"></i></a></li>";
				$noOfTeams++;
			}
			echo "</ul>\n";
			
			?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-inline">
				<input type="hidden" name="action" value="addteam">
				<input type="hidden" name="site" value="<?php echo $site; ?>">
				<input type="hidden" name="round" value="<?php echo $roundid; ?>">
				<input type="hidden" name="group" value="<?php echo escapeOutput($groupName); ?>">
				
				<?php 
				FormBuilder::createForeignKeyField($i18n, "teamid", array("entity" => "club", "jointable" => "verein", "labelcolumns" => "name"), "");
				?>
				
				<input type="submit" class="btn btn-small" value="<?php echo $i18n->getMessage("managecuprounds_groups_addteam"); ?>">
			</form>
			<?php
			
			echo "</td>";
			echo "<td>";
			
			?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-inline">
				<input type="hidden" name="action" value="saveranks">
				<input type="hidden" name="site" value="<?php echo $site; ?>">
				<input type="hidden" name="round" value="<?php echo $roundid; ?>">
				<input type="hidden" name="group" value="<?php echo escapeOutput($groupName); ?>">
			<?php 
			echo "<ol>";
			for ($rank = 1; $rank <= $noOfTeams; $rank++) {
				
				echo "<li><select name=\"rank_$rank\"><option></option>";
				foreach ($rounds as $roundItem) {

					echo "<option value=\"" . $roundItem["id"] . "\"";
					if (isset($rankConfigs[$groupName][$rank]) && $rankConfigs[$groupName][$rank] == $roundItem["id"]) {
						echo " selected";
					}
					echo ">". escapeOutput($roundItem["name"]) . "</option>";
				}
				echo "</select></li>\n";
				
			}
			echo "</ol>";
			?>
			<input type="submit" class="btn btn-small" value="<?php echo $i18n->getMessage("button_save"); ?>">
			</form>
			<?php
			echo "</td>";
			echo "<td><a href=\"?site=". $site . "&round=". $roundid . "&action=edit&group=". urlencode(escapeOutput($groupName)) . "\" title=\"". $i18n->getMessage("manage_edit") . "\"><i class=\"icon-pencil\"></i></a></td>";
			echo "<td><a class=\"deleteLink\" href=\"?site=". $site . "&round=". $roundid . "&group=". urlencode(escapeOutput($groupName)) . "&action=delete\" title=\"". $i18n->getMessage("manage_delete") . "\"><i class=\"icon-trash\"></i></a></td>";
			
			echo "</tr>\n";
		}
		?>
		</tbody>
	</table>
	
	<?php 
	// GENERATE FORM
	if ($action == "generateschedule") {
		if ($admin["r_demo"]) {
			throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
		}
		
		$rounds = (int) $_POST['rounds'];
		$dateObj = DateTime::createFromFormat($website->getConfig("date_format") .", H:i",
				$_POST["firstmatchday_date"] .", ". $_POST["firstmatchday_time"]);
		$timeBreakSeconds = 3600 * 24 * $_POST['timebreak'];
		
		$dbTable = $website->getConfig("db_prefix") . "_spiel";
		
		// create a separate schedule for every group
		foreach($groups as $groupName => $groupItems) {

			// delete existing matches
			$db->queryDelete($dbTable, "pokalname = '%s' AND pokalrunde = '%s' AND pokalgruppe = '%s' AND berechnet = '0'", 
					array($round["cup_name"], $round["round_name"], $groupName));

			$teamIds = array();
			foreach($groupItems as $groupItem) {
				$teamIds[] = $groupItem["team_id"];
			}
			
			$schedule = ScheduleGenerator::createRoundRobinSchedule($teamIds);
			$numberOfMatchDaysPerRound = count($schedule);
			
			// create match pairs for after first round
			for ($roundNo = 2; $roundNo <= $rounds; $roundNo++) {
			
				$startMatchday = count($schedule) + 1;
				$endMatchday = $startMatchday + $numberOfMatchDaysPerRound - 1;
				for ($matchday = $startMatchday; $matchday <= $endMatchday; $matchday++) {
					$originalMatchDay = $matchday - $numberOfMatchDaysPerRound;
						
					foreach ($schedule[$originalMatchDay] as $match) {
						$homeTeam = $match[1];
						$guestTeam = $match[0];
						$schedule[$matchday][] = array($homeTeam, $guestTeam);
					}
				}
			}
			
			// create matches
			$matchTimestamp = $dateObj->getTimestamp();
			foreach($schedule as $matchDay => $matches) {
				foreach ($matches as $match) {
			
					$homeTeam = $match[0];
					$guestTeam = $match[1];
						
					$matchcolumns = array();
					$matchcolumns["spieltyp"] = "Pokalspiel";
					$matchcolumns["pokalname"] = $round["cup_name"];
					$matchcolumns["pokalrunde"] = $round["round_name"];
					$matchcolumns["pokalgruppe"] = $groupName;
					$matchcolumns["home_verein"] = $homeTeam;
					$matchcolumns["gast_verein"] = $guestTeam;
					$matchcolumns["datum"] = $matchTimestamp;
						
					$db->queryInsert($matchcolumns, $dbTable);
				}
			
				$matchTimestamp += $timeBreakSeconds;
			}

		}
		
		echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");
	}
	
	// count matches
	$result = $db->querySelect("COUNT(*) AS hits", $website->getConfig("db_prefix") . "_spiel", 
					"pokalname = '%s' AND pokalrunde = '%s'", array($round["cup_name"], $round["round_name"]));
	$matches = $result->fetch_array();
	$result->free();
	
	$matchesUrl = "?site=manage&entity=match&" . http_build_query(array(
			"entity_match_pokalname" => escapeOutput($round["cup_name"]),
			"entity_match_pokalrunde" => escapeOutput($round["round_name"])));
	
	?>
	
	<div class="well">
		<?php if (isset($matches["hits"]) && $matches["hits"]) { ?>
		<p><a href="<?php echo $matchesUrl; ?>"><strong><?php echo $matches["hits"]; ?></strong> <?php echo $i18n->getMessage("managecuprounds_groups_created_matches"); ?></a></p>
		<?php } ?>
		<p><a href="#generateModal" role="button" class="btn" data-toggle="modal"><?php echo $i18n->getMessage("managecuprounds_groups_open_generate_matches_popup"); ?></a></p>
	</div>
	
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form-horizontal">
	    <input type="hidden" name="action" value="generateschedule">
		<input type="hidden" name="site" value="<?php echo $site; ?>">
		<input type="hidden" name="round" value="<?php echo $roundid; ?>">
		<div id="generateModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="generateModalLabel" aria-hidden="true">
		  <div class="modal-header">
		    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">??</button>
		    <h3 id="generateModalLabel"><?php echo $i18n->getMessage("managecuprounds_groups_generate_title"); ?></h3>
		  </div>
		  <div class="modal-body">
		  	<div class="alert">
		  		<?php echo $i18n->getMessage("managecuprounds_groups_generate_alert"); ?>
		  	</div>
			<?php 
			foreach ($generateFormFields as $fieldId => $fieldInfo) {
				echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldInfo["value"], "schedulegenerator_label_");
			}	
			?>
		  </div>
		  <div class="modal-footer">
		    <button class="btn btn-primary" type="submit"><?php echo $i18n->getMessage("managecuprounds_groups_generate_submit"); ?></button>
		    <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo $i18n->getMessage("button_cancel"); ?></button>
		  </div>
		</div>
	</form>
	
	<?php 
}

// Create form
?>

  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form-horizontal">
    <input type="hidden" name="action" value="create">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	<input type="hidden" name="round" value="<?php echo $roundid; ?>">
	
	<fieldset>
    <legend><?php echo $i18n->getMessage("managecuprounds_groups_label_create"); ?></legend>
    
	<?php 
	foreach ($formFields as $fieldId => $fieldInfo) {
		echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldInfo["value"], "managecuprounds_group_label_");
	}	
	?>
	<div>
	<label><?php echo $i18n->getMessage("managecuprounds_groups_label_selectteams"); ?></label>
	</div>
	
		<div style="width: 600px; height: 300px; overflow: auto; border: 1px solid #cccccc;">
			<table class="table table-striped table-hover">
				<colgroup>
					<col style="width: 30px">
					<col>
					<col>
				</colgroup>
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th><?php echo $i18n->getMessage("entity_club")?></th>
						<th><?php echo $i18n->getMessage("entity_league")?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach ($teams as $team) {
						echo "<tr>";
						echo "<td><input type=\"checkbox\" name=\"teams[]\" value=\"". $team["team_id"] . "\"></td>";
						echo "<td class=\"tableRowSelectionCell\">". escapeOutput($team["team_name"]) . "</td>";
						echo "<td class=\"tableRowSelectionCell\">". escapeOutput($team["league_name"] . " (" . $team["league_country"] . ")") . "</td>";
						echo "</tr>\n";
					}
					?>
				</tbody>
			</table>
		</div>
	
	</fieldset>
	<div class="form-actions">
		<input type="submit" class="btn btn-primary" accesskey="s" title="Alt + s" value="<?php echo $i18n->getMessage("button_save"); ?>"> 
	<?php 
	echo "<a href=\"?site=managecuprounds&cup=". $round["cup_id"] . "\" class=\"btn\">" . $i18n->getMessage("button_cancel") ."</a>";
	
	?>
	
	</div>    
  </form>