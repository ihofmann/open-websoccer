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

$mainTitle = $i18n->getMessage("teamsgenerator_navlabel");

echo "<h1>$mainTitle</h1>";

if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin[$page["permissionrole"]]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

if (!$show) {

  ?>
  
  <p><?php echo $i18n->getMessage("teamsgenerator_intro"); ?></p>

  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form-horizontal">
    <input type="hidden" name="show" value="generate">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	
	<fieldset>
    <legend><?php echo $i18n->getMessage("generator_label"); ?></legend>
    
	<?php 
	$formFields = array();
	
	$formFields["league"] = array("type" => "foreign_key", "labelcolumns" => "land,name", "jointable" => "liga", "entity" => "league", "value" => "", "required" => "true");
	$formFields["numberofteams"] = array("type" => "number", "value" => 20, "required" => "true");
	$formFields["budget"] = array("type" => "number", "value" => 5000000, "required" => "true");
	
	$formFields["generatestadium"] = array("type" => "boolean", "value" => 1);
	$formFields["stadiumpattern"] = array("type" => "text", "value" => "Stadion %s");
	
	$formFields["stadium_p_stands"] = array("type" => "number", "value" => 1000);
	$formFields["stadium_p_seats"] = array("type" => "number", "value" => 5000);
	$formFields["stadium_p_stands_grand"] = array("type" => "number", "value" => 0);
	$formFields["stadium_p_seats_grand"] = array("type" => "number", "value" => 10000);
	$formFields["stadium_p_vip"] = array("type" => "number", "value" => 100);
	
	foreach ($formFields as $fieldId => $fieldInfo) {
		echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldInfo["value"], "generator_label_");
	}	
	?>
	</fieldset>
	<div class="form-actions">
		<input type="submit" class="btn btn-primary" accesskey="s" title="Alt + s" value="<?php echo $i18n->getMessage("generator_button"); ?>"> 
		<input type="reset" class="btn" value="<?php echo $i18n->getMessage("button_reset"); ?>">
	</div>    
  </form>

  <?php

}

//********** validate, generate **********
elseif ($show == "generate") {

  if (!isset($_POST['league']) || $_POST['league'] <= 0) $err[] = $i18n->getMessage("generator_validationerror_noleague");
  if ($_POST['numberofteams'] <= 0) $err[] = $i18n->getMessage("generator_validationerror_numberofitems");
  if ($_POST['numberofteams'] > 100) $err[] = $i18n->getMessage("generator_validationerror_numberofitems_max");
  if ($admin['r_demo']) $err[] = $i18n->getMessage("validationerror_no_changes_as_demo");

  if (isset($err)) {

    include("validationerror.inc.php");

  }
  else {

	DataGeneratorService::generateTeams($website, $db, $_POST['numberofteams'], $_POST['league'], $_POST['budget'],
		(isset($_POST['generatestadium']) && $_POST['generatestadium']), $_POST['stadiumpattern'], $_POST['stadium_p_stands'], $_POST['stadium_p_seats'], $_POST['stadium_p_stands_grand'], $_POST['stadium_p_seats_grand'], $_POST['stadium_p_vip'] );	
	
	echo createSuccessMessage($i18n->getMessage("generator_success"), "");

      echo "<p>&raquo; <a href=\"?site=". $site ."\">". $i18n->getMessage("back_label") . "</a></p>\n";

  }

}

?>
