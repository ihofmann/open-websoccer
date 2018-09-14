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

$mainTitle = $i18n->getMessage('entitylogging_navlabel');

if (!$admin['r_admin'] && !$admin['r_demo']) {
  echo '<p>'. $i18n->getMessage('error_access_denied') . '</p>';
  exit;
}

if (!$show) {

  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <p><?php echo $i18n->getMessage('entitylogging_intro'); ?></p>
  
  <code>&lt;overview delete=&quot;true&quot; edit=&quot;true&quot; <strong>logging=&quot;true&quot; loggingcolumns=&quot;name,liga_id&quot;</strong>&gt;</code>

  <?php

  $datei = 'config/entitylog.php';

  if (!file_exists($datei)) echo createErrorMessage($i18n->getMessage('alert_error_title'), $i18n->getMessage('all_logging_filenotfound'));
  else {

    $datei_gr = filesize($datei);

    if (!$datei_gr) echo '<p>'. $i18n->getMessage('empty_list') . '</p>';
    else {

      ?>

            <table class='table table-bordered table-striped' style='margin-top: 10px'>
              <tr>
                <th><?php echo $i18n->getMessage('entitylogging_label_no'); ?></th>
                <th><?php echo $i18n->getMessage('entitylogging_label_time'); ?></th>
                <th><?php echo $i18n->getMessage('entitylogging_label_user'); ?></th>
                <th><?php echo $i18n->getMessage('entitylogging_label_type'); ?></th>
                <th><?php echo $i18n->getMessage('entitylogging_label_data'); ?></th>
              </tr>
              <?php

              $file = file($datei);
              $lines = count($file);
              $min = $lines - 50;
              if ($min < 0) $min = 0;

              for ($i = $lines-1; $i >= $min; $i--) {
				$line = $file[$i];

                $row = explode(';', $line);
				
				$n = $i + 1;
                echo '<tr>
                  <td><b>'. $n .'</b></td>
                  <td>'. $row[0] .'</td>
                  <td>'. escapeOutput($row[1]) .' ('. escapeOutput($row[2]) . ')</td>
                  <td>'; 
                  
                  	if ($row[3] == LOG_TYPE_EDIT) {
						echo '<span class=\'label label-info\'><i class=\'icon-white icon-pencil\'></i> '. $i18n->getMessage('entitylogging_action_edit') . '</span>';
					} elseif ($row[3] == LOG_TYPE_DELETE) {
						echo '<span class=\'label label-important\'><i class=\'icon-white icon-trash\'></i> '. $i18n->getMessage('entitylogging_action_delete') . '</span>';
					} else {
						echo $row[3];
					}
                  echo '</td>
				  <td>'. $i18n->getMessage('entity_' . $row[4]) .': { ';
                  	$itemFields = json_decode($row[5], TRUE);
                  	$firstField = TRUE;
                  	foreach ($itemFields as $fieldKey => $fieldValue) {
						if ($firstField) {
							$firstField = FALSE;
						} else {
							echo ', ';
						}
						
						echo $fieldKey . ': ' . escapeOutput($fieldValue);
						
					}
				   echo ' }</td>
                </tr>';
              }

              ?>
            </table>

      <?php

    }

  }

}


?>
