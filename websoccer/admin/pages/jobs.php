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

$mainTitle = $i18n->getMessage("jobs_navlabel");

if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin[$page["permissionrole"]]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

if (!$show) {

  ?>

  <h1><?php echo $mainTitle; ?></h1>

  <p><?php echo $i18n->getMessage("jobs_introduction"); ?></p>
  
  <?php 
  	if ($action == "execute" && !$admin["r_demo"]) {
		$jobId = $_REQUEST["id"];

		try {
			$jobConfig = JobDataService::getJob($website, $db, $jobId);
			if (!$jobConfig) {
				throw new Exception("Job config not found.");
			}
			
			$jobClass = $jobConfig['class'];
			if (class_exists($jobClass)) {
				$job = new $jobClass($website, $db, $i18n, $jobId);
			} else {
				throw new Exception("class not found: " . $jobClass);
			}
			
			$job->execute();
			// Destroy the job object so that AbstractJob::__destruct() runs
			// immediately, updating last_ping before the table is rendered.
			unset($job);

			echo createSuccessMessage($i18n->getMessage("jobs_executed"), "");
		} catch (Exception $e) {
			echo createErrorMessage($i18n->getMessage("subpage_error_title"), $e->getMessage());
		}
	}
  ?>
  
  <table class="table table-striped">
  	<thead>
  		<tr>
  			<th><?php echo $i18n->getMessage("jobs_head_id"); ?></th>
  			<th><?php echo $i18n->getMessage("jobs_head_name"); ?></th>
  			<th><?php echo $i18n->getMessage("jobs_head_last_execution"); ?></th>
  			<th><?php echo $i18n->getMessage("jobs_head_execute"); ?></th>
  		</tr>
  	</thead>
  	<tbody>
  	<?php 
		$allJobIds = array();
		
		foreach (JobDataService::getJobs($website, $db) as $item) {
			echo "<tr>";
			
			$jobid = $item['id'];
			$allJobIds[] = $jobid;
			
			$i18nJobNameAttr = "name_" . $i18n->getCurrentLanguage();
			if (isset($item[$i18nJobNameAttr]) && strlen($item[$i18nJobNameAttr])) {
				$name = $item[$i18nJobNameAttr];
			} else {
				$name = $item['name'];
			}
			
			$lastPing = (int) $item['last_ping'];
			$error = (string) $item['error'];
			
			echo "<td><code>" . escapeOutput($jobid) . "</code></td>";
			echo "<td>" . escapeOutput($name);
			if (strlen($error)) {
				echo createErrorMessage($i18n->getMessage("subpage_error_title"), escapeOutput($error));
			}
			echo "</td>";
			echo "<td>";
			if ($lastPing > 0) {
				echo $website->getFormattedDatetime($lastPing);
			} else {
				echo "-";
			}
			echo "</td>";
			echo "<td>";
			echo "<a href=\"?site=". $site . "&amp;action=execute&amp;id=". urlencode($jobid) . "\" class=\"btn btn-primary\">". $i18n->getMessage("jobs_button_execute_now") ."</a>";
			echo "</td>";
			
			echo "</tr>";
		}
		
	?>
  	</tbody>
  </table>
  
  <?php
  // Build cronjob examples
  $executeJobPath = realpath(BASE_FOLDER . '/webservices/executeJob.php');
  $securityKey = $website->getConfig('webjobexecution_key');
  
  // Job IDs for the "all other jobs" cronjob (every job except 'sim')
  $otherJobIds = array_diff($allJobIds, array('sim'));
  $otherJobIdsParam = implode(',', $otherJobIds);
  
  $simCronLine = '* * * * * php ' . $executeJobPath . ' sectoken=' . $securityKey . ' jobid=sim';
  $othersCronLine = '*/15 * * * * php ' . $executeJobPath . ' sectoken=' . $securityKey . ' jobid=' . $otherJobIdsParam;
  
  // Per-job cron lines
  $perJobCronLines = '';
  foreach (JobDataService::getJobs($website, $db) as $item) {
	  $jid = $item['id'];
	  $interval = (int) $item['interval'];
	  if ($interval > 1) {
		  $cronSchedule = '*/' . $interval . ' * * * *';
	  } else {
		  $cronSchedule = '* * * * *';
	  }
	  $perJobCronLines .= $cronSchedule . ' php ' . $executeJobPath . ' sectoken=' . $securityKey . ' jobid=' . $jid . "\n";
  }
  ?>
  
  <h2><?php echo $i18n->getMessage("jobs_cronjobs_title"); ?></h2>
  
  <p><?php echo $i18n->getMessage("jobs_cronjobs_intro"); ?></p>
  
  <h3><?php echo $i18n->getMessage("jobs_cronjobs_recommended_title"); ?></h3>
  <p><?php echo $i18n->getMessage("jobs_cronjobs_recommended_text"); ?></p>
  <pre><code><?php echo escapeOutput($simCronLine . "\n" . $othersCronLine); ?></code></pre>
  
  <h3><?php echo $i18n->getMessage("jobs_cronjobs_per_job_title"); ?></h3>
  <p><?php echo $i18n->getMessage("jobs_cronjobs_per_job_text"); ?></p>
  <pre><code><?php echo escapeOutput(trim($perJobCronLines)); ?></code></pre>
  
  <?php

}

?>
