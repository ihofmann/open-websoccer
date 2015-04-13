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

  $datei = 'config/adminlog.php';

  if (!file_exists($datei)) echo createErrorMessage($i18n->getMessage('alert_error_title'), $i18n->getMessage('all_logging_filenotfound'));
  elseif ($admin['r_demo']) echo createErrorMessage($i18n->getMessage('error_access_denied'), '');
  else {

    if ($action == 'leeren') {

      $fp = fopen($datei, 'w+');
      $ip = getenv('REMOTE_ADDR');
      $content = 'Truncated by '. $admin['name'] .' (id: '. $admin['id'] . '), '. $ip .', '. date('d.m.y - H:i:s');
      fwrite($fp, $content);
      fclose($fp);

      if ($fp) echo createSuccessMessage($i18n->getMessage('all_logging_alert_logfile_truncated'), '');
      else echo createErrorMessage($i18n->getMessage('alert_error_title'), $i18n->getMessage('all_logging_error_not_truncated'));

    }

    $datei_gr = filesize($datei);
    $gr_kb = round($datei_gr / 1024);
    if ($datei_gr && !$gr_kb) $gr_kb = 1;

    echo '<div class=\'well\'>'. sprintf($i18n->getMessage('all_logging_filesize'), number_format($gr_kb, 0, ' ', ',')) .'</div>';

    if (!$datei_gr) echo '<p>'. $i18n->getMessage('empty_list') . '</p>';
    else {

      ?>

      <form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
        <input type='hidden' name='action' value='leeren'>
		<input type='hidden' name='site' value='<?php echo $site; ?>'>
        <p><input type='submit' class='btn' value='<?php echo $i18n->getMessage('all_logging_button_empty_file'); ?>'></p>
        
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

              $file = file($datei);
              $lines = count($file);
              $min = $lines - 50;
              if ($min < 0) $min = 0;

              for ($i = $lines-1; $i >= $min; $i--) {
				$line = $file[$i];

                $row = explode(', ', $line);
				
				$n = $i + 1;
                echo '<tr>
                  <td><b>'. $n .'</b></td>
                  <td>'. escapeOutput($row[0]) .'</td>
                  <td>'. escapeOutput($row[1]) .'</td>
                  <td>'. escapeOutput($row[2]) .'</td>
                </tr>';
              }

              ?>
            </table>

      <?php

    }

  }

}


?>
