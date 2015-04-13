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

function prepareFielfValueForSaving($fieldValue) {

	$preparedValue = trim($fieldValue);

	// remove automatically added slashes, since config file writer will anyway add slashes.
	if (get_magic_quotes_gpc()) {
		$preparedValue = stripslashes($fieldValue);
	}
	
	return $preparedValue;
}

$mainTitle = $i18n->getMessage('all_settings_title');

include(CONFIGCACHE_SETTINGS);

if (!$admin['r_admin'] && !$admin['r_demo']) {
  echo '<p>'. $i18n->getMessage('error_access_denied') . '</p>';
  exit;
}

if (!$show) {
	
	// get tabs
	$tabs = array();
	foreach ($setting as $settingId => $settingData) {
		$settingInfo = json_decode($settingData, true);
		$tabs[$settingInfo['category']][$settingId] = $settingInfo;
	}

  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post' class='form-horizontal'>
    <input type='hidden' name='show' value='speichern'>
	<input type='hidden' name='site' value='<?php echo $site; ?>'>
	

	<ul class='nav nav-tabs'>
	<?php 
	// tab headers
	$firstTab = TRUE;
	foreach ($tabs as $tabId => $settings) {
		echo '<li';
		if ($firstTab) echo ' class=\'active\'';
		echo '><a href=\'#'. $tabId . '\' data-toggle=\'tab\'>'. $i18n->getMessage('settings_tab_' . $tabId) .'</a></li>';
		$firstTab = FALSE;
	}
	?>
	</ul>	
	
	<div class='tab-content'>
	
	<?php 
	// tab content
	$firstTab = TRUE;
	foreach ($tabs as $tabId => $settings) {
		echo '<div class=\'tab-pane';
		if ($firstTab) echo ' active';
		echo '\' id=\''. $tabId . '\'>';
		
		foreach ($settings as $settingId => $settingInfo) {
			echo FormBuilder::createFormGroup($i18n, $settingId, $settingInfo, $website->getConfig($settingId), 'settings_label_');
		}
		
		echo '</div>';
		$firstTab = FALSE;
	}
	?>	
	 
	</div>	
	
	<div class='form-actions'>
		<input type='submit' class='btn btn-primary' accesskey='s' title='Alt + s' value='<?php echo $i18n->getMessage('button_save'); ?>'> 
		<input type='reset' class='btn' value='<?php echo $i18n->getMessage('button_reset'); ?>'>
	</div>
         
  </form>

  <?php

}

//********** Validate and save **********
elseif ($show == 'speichern') {

  if ($admin['r_demo']) $err[] = $i18n->getMessage('validationerror_no_changes_as_demo');

  //##### output errors #####
  if (isset($err)) {

    include('validationerror.inc.php');

  }
  //##### Save #####
  else {
  	
  	$newSettings = array();
  	
  	foreach ($setting as $settingId => $settingData) {
  		$newSettings[$settingId] = (isset($_POST[$settingId])) ? prepareFielfValueForSaving($_POST[$settingId]) : '';
  	}
  	
  	$cf = ConfigFileWriter::getInstance($conf);
  	$cf->saveSettings($newSettings); 

	include('success.inc.php');
	
	echo createWarningMessage($i18n->getMessage('settings_saved_note_restartjobs'),
		$i18n->getMessage('settings_saved_note_restartjobs_details'));
	
  }

}

?>
