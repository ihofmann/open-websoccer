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

// get XML config
$selectedLang = (isset($_POST["lang"])) ? $_POST["lang"] : $i18n->getCurrentLanguage();

$termsFile = BASE_FOLDER . "/admin/config/termsandconditions.xml";
if (!file_exists($termsFile)) {
	throw new Exception("File does not exist: " . $termsFile);
}

$xml = simplexml_load_file($termsFile);

$termsConfig = $xml->xpath("//pagecontent[@lang = '". $selectedLang . "'][1]");
if (!$termsConfig) {
	throw new Exception("No terms and conditions available for this language. Create manually a new entry at " . $termsFile);
}

//********** form **********
if (!$show) {
  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <p><?php echo $i18n->getMessage("termsandconditions_introduction"); ?></p>
  
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form-inline">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	
	<label for="lang"><?php echo $i18n->getMessage("termsandconditions_label_language"); ?></label>
	<select name="lang" id="lang">
		<?php 
		foreach($i18n->getSupportedLanguages() as $language) {
			echo "<option value=\"$language\"";
			if ($language == $selectedLang) echo " selected";
			echo ">$language</option>";
		}
		?>
	</select>
	<button type="submit" class="btn"><?php echo $i18n->getMessage("button_display"); ?></button>
  </form>
  
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form-horizontal">
    <input type="hidden" name="show" value="save">
    <input type="hidden" name="lang" value="<?php echo escapeOutput($selectedLang); ?>">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	
	<fieldset>
	<?php 
	$formFields = array();
	
	$terms = (string) $termsConfig[0];
	
	$formFields["content"] = array("type" => "html", "value" => $terms, "required" => "true");
	foreach ($formFields as $fieldId => $fieldInfo) {
		echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldInfo["value"], "imprint_label_");
	}	
	?>
	</fieldset>
	<div class="form-actions">
		<input type="submit" class="btn btn-primary" accesskey="s" title="Alt + s" value="<?php echo $i18n->getMessage("button_save"); ?>"> 
		<input type="reset" class="btn" value="<?php echo $i18n->getMessage("button_reset"); ?>">
	</div>    
  </form>

  <?php

}

//********** save **********
elseif ($show == "save") {

  if (!isset($_POST['content']) || !strlen($_POST['content'])) $err[] = $i18n->getMessage("imprint_validationerror_content");
  if (!is_writable($termsFile)) $err[] = $i18n->getMessage("termsandconditions_err_filenotwritable", $termsFile);
  if ($admin['r_demo']) $err[] = $i18n->getMessage("validationerror_no_changes_as_demo");

  if (isset($err)) {

    include("validationerror.inc.php");

  }
  else {

    echo "<h1>". $mainTitle ." &raquo; ". $i18n->getMessage("subpage_save_title") . "</h1>";

    $termsContent = stripslashes($_POST['content']);
	    
    // replace CDATA. Well, not easy with nice PHP, so this trick does it somehow...
    $node= dom_import_simplexml($termsConfig[0]); 
	$no = $node->ownerDocument; 
	   
	// remove existing CDATA
	foreach($node->childNodes as $child) {
	   	if ($child->nodeType == XML_CDATA_SECTION_NODE) {
	   		$node->removeChild($child);
	   	}
	}
	// add new CDATA
	$node->appendChild($no->createCDATASection($termsContent)); 
	   
	$xml->asXML($termsFile);
    
	echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");

    echo "<p>&raquo; <a href=\"?site=". $site ."\">". $i18n->getMessage("back_label") . "</a></p>\n";

  }

}

?>
