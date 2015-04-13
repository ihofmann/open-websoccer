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

$mainTitle = $i18n->getMessage('imprint_navlabel');

if (!$admin['r_admin'] && !$admin['r_demo'] && !$admin[$page['permissionrole']]) {
	throw new Exception($i18n->getMessage('error_access_denied'));
}

if (!$show) {

  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <p><?php echo escapeOutput($i18n->getMessage('imprint_introduction')); ?></p>
  
  <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post' class='form-horizontal'>
    <input type='hidden' name='show' value='save'>
	<input type='hidden' name='site' value='<?php echo $site; ?>'>
	
	<fieldset>
	<?php 
	$formFields = array();
	
	$filecontent = '';
	if (file_exists(IMPRINT_FILE)) {
		$filecontent = file_get_contents(IMPRINT_FILE);
	}
	
	$formFields['content'] = array('type' => 'html', 'value' => $filecontent, 'required' => 'true');
	foreach ($formFields as $fieldId => $fieldInfo) {
		echo FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldInfo['value'], 'imprint_label_');
	}	
	?>
	</fieldset>
	<div class='form-actions'>
		<input type='submit' class='btn btn-primary' accesskey='s' title='Alt + s' value='<?php echo $i18n->getMessage('button_save'); ?>'> 
		<input type='reset' class='btn' value='<?php echo $i18n->getMessage('button_reset'); ?>'>
	</div>    
  </form>

  
  <?php

}

elseif ($show == 'save') {

  if (!isset($_POST['content']) || !$_POST['content']) $err[] = $i18n->getMessage('imprint_validationerror_content');
  if ($admin['r_demo']) $err[] = $i18n->getMessage('validationerror_no_changes_as_demo');

  if (isset($err)) {

    include('validationerror.inc.php');

  }
  else {

    echo '<h1>'. $mainTitle .' &raquo; '. $i18n->getMessage('subpage_save_title') . '</h1>';

    $fw = new FileWriter(IMPRINT_FILE);
    $fw->writeLine(stripslashes($_POST['content']));
    $fw->close();
    
	echo createSuccessMessage($i18n->getMessage('alert_save_success'), '');

      echo '<p>&raquo; <a href=\'?site='. $site .'\'>'. $i18n->getMessage('back_label') . '</a></p>';

  }

}

?>
