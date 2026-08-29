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

$mainTitle = $i18n->getMessage("termsandconditions_navlabel");

if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin[$page["permissionrole"]]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

// get page for selected language
$selectedLang = (isset($_POST["lang"])) ? $_POST["lang"] : $i18n->getCurrentLanguage();

$termsPage = PageDataService::getByTypeAndLanguage(
	$website,
	$db,
	PageDataService::TERMS_AND_CONDITIONS_TYPE,
	$selectedLang
);

//********** form **********
if (!$show) {
  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <p><?php echo $i18n->getMessage("termsandconditions_introduction"); ?></p>
  
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="d-flex flex-wrap gap-2 align-items-center mb-3">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	
	<label for="lang"><?php echo $i18n->getMessage("termsandconditions_label_language"); ?></label>
	<select class="form-select" name="lang" id="lang">
		<?php 
		foreach($i18n->getSupportedLanguages() as $language) {
			echo "<option value=\"$language\"";
			if ($language == $selectedLang) echo " selected";
			echo ">$language</option>";
		}
		?>
	</select>
	<button type="submit" class="btn btn-outline-primary"><?php echo $i18n->getMessage("button_display"); ?></button>
  </form>
  
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="row">
    <input type="hidden" name="show" value="save">
    <input type="hidden" name="lang" value="<?php echo escapeOutput($selectedLang); ?>">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	
	<fieldset>
	<?php 
	$formFields = array();
	
	$terms = ($termsPage) ? $termsPage["content"] : '';
	
	$formFields["content"] = array("type" => "html", "value" => $terms, "required" => "true");
	foreach ($formFields as $fieldId => $fieldInfo) {
		echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldInfo["value"], "imprint_label_");
	}	
	?>
	</fieldset>
	<div class="d-flex gap-2 justify-content-center p-3">
		<input type="submit" class="btn btn-primary" accesskey="s" title="Alt + s" value="<?php echo $i18n->getMessage("button_save"); ?>"> 
		<input type="reset" class="btn btn-secondary" value="<?php echo $i18n->getMessage("button_reset"); ?>">
	</div>    
  </form>

  <?php

}

//********** save **********
elseif ($show == "save") {

  if (!isset($_POST['content']) || !strlen($_POST['content'])) $err[] = $i18n->getMessage("imprint_validationerror_content");
  if ($admin['r_demo']) $err[] = $i18n->getMessage("validationerror_no_changes_as_demo");

  if (isset($err)) {

    include("validationerror.inc.php");

  }
  else {

    echo "<h1>". $mainTitle ." &raquo; ". $i18n->getMessage("subpage_save_title") . "</h1>";

    PageDataService::save(
		$website,
		$db,
		PageDataService::TERMS_AND_CONDITIONS_TYPE,
		$selectedLang,
		stripslashes($_POST['content'])
	);
    
	echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");

    echo "<p>&raquo; <a href=\"?site=". $site ."\">". $i18n->getMessage("back_label") . "</a></p>\n";

  }

}

?>
