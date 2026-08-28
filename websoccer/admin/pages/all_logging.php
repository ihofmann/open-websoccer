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

$mainTitle = $i18n->getMessage('all_logging_title');

if (!$admin['r_admin'] && !$admin['r_demo']) {
  echo '<p>'. $i18n->getMessage('error_access_denied') . '</p>';
  exit;
}

if (!$show) {

  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <p><?php echo $i18n->getMessage('all_logging_intro'); ?></p>

  <?php

  if ($admin['r_demo']) echo createErrorMessage($i18n->getMessage('error_access_denied'), '');
  else {
    if ($action == 'clear_old') {
      $threshold = strtotime('-6 months', $website->getNowAsTimestamp());
      AdminLogDataService::deleteOlderThan($website, $db, $threshold);
      echo createSuccessMessage($i18n->getMessage('all_logging_alert_logs_deleted'), '');
    }

    $logs = AdminLogDataService::getLatest($website, $db, 50);
    if (!count($logs)) echo '<p>'. $i18n->getMessage('empty_list') . '</p>';
    else {

      ?>

      <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
        <input type='hidden' name='action' value='clear_old'>
		<input type='hidden' name='site' value='<?php echo $site; ?>'>
        <p><input type='submit' class='btn btn-outline-primary' value='<?php echo $i18n->getMessage('all_logging_button_clear_old'); ?>'></p>
        
      </form>

      <p>(<?php echo $i18n->getMessage('all_logging_only_last_entries_shown'); ?>)</p>

            <table class='table table-bordered table-striped'>
              <tr>
                <th><?php echo $i18n->getMessage('all_logging_label_no'); ?></th>
                <th><?php echo $i18n->getMessage('all_logging_label_user'); ?></th>
                <th><?php echo $i18n->getMessage('all_logging_label_ip'); ?></th>
                <th><?php echo $i18n->getMessage('all_logging_label_time'); ?></th>
              </tr>
              <?php

              foreach ($logs as $i => $log) {
                echo '<tr>
                  <td><b>'. ($i + 1) .'</b></td>
                  <td>'. escapeOutput($log['user_name']) .'</td>
                  <td>'. escapeOutput($log['ip']) .'</td>
                  <td>'. escapeOutput($website->getFormattedDatetime($log['created_date'])) .'</td>
                </tr>';
              }

              ?>
            </table>

      <?php

    }

  }

}


?>
