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


if (!$showOverview) {
	
$columns = "*";
$whereCondition = "id = %d";
$result = $db->querySelect($columns, $dbTable, $whereCondition, $id, 1);
$row = $result->fetch_array();
if (!$row) {
	throw new Exception("Invalid URL - Item does not exist.");
}
$result->free();
	
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>#item<?php echo $row["id"]; ?>" method="post" class="form-horizontal"<?php 
	if ($enableFileUpload) echo " enctype=\"multipart/form-data\""; 
?>>
	<input type="hidden" name="show" value="<?php echo $show; ?>">
	<input type="hidden" name="entity" value="<?php echo $entity; ?>">
	<input type="hidden" name="action" value="save">
	<input type="hidden" name="id" value="<?php echo $id; ?>">
	<input type="hidden" name="site" value="<?php echo $site; ?>">
	<?php 
	if (isset($_REQUEST["page"])) {
	?>
	<input type="hidden" name="page" value="<?php echo escapeOutput($_REQUEST["page"]); ?>">
	<?php 
	}
	?>
	<fieldset>
    <legend><?php echo $i18n->getMessage("manage_edit_title"); ?></legend>
	
	<?php 
	foreach ($formFields as $fieldId => $fieldInfo) {
		$fieldValue = ($action == "save" && isset($_POST[$fieldId])) ? $_POST[$fieldId] : $row[$fieldId];
		echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldValue, $labelPrefix);
	}	
	?>
	</fieldset>
	<div class="form-actions">
		<input type="submit" class="btn btn-primary" accesskey="s" title="Alt + s" value="<?php echo $i18n->getMessage("button_save"); ?>"> 
		<a class="btn" href="?site=<?php echo $site; ?>&entity=<?php echo $entity; ?>"><?php echo $i18n->getMessage("button_cancel"); ?></a>
	</div>
         
</form>
<?php 
}
?>

