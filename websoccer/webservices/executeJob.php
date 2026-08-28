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

define('BASE_FOLDER', __DIR__ .'/..');
include(BASE_FOLDER . '/admin/config/global.inc.php');

// When invoked from the command line (e.g. via CronJob), parse the
// key=value arguments into $_REQUEST so the rest of the script works
// the same way as a web request.
if (php_sapi_name() === 'cli' && isset($argv)) {
	for ($i = 1; $i < count($argv); $i++) {
		$parts = explode('=', $argv[$i], 2);
		if (count($parts) === 2) {
			$_REQUEST[$parts[0]] = $parts[1];
			$_GET[$parts[0]] = $parts[1];
		}
	}
}

// execution enabled?
if (!$website->getConfig('webjobexecution_enabled')) {
	die('External job execution disabled');
}

// do not execute if site is in offline mode
if ($website->getConfig('offline') == 'offline') {
	die('Site is in offline mode');
}

if (!isset($_REQUEST['sectoken'])) {
	die('no security token provided');
}
if (!isset($_REQUEST['jobid'])) {
	die('no job ID provided');
}

$securityToken = $_REQUEST['sectoken'];
$jobIdParam = $_REQUEST['jobid'];

// check security token
if ($website->getConfig('webjobexecution_key') !== $securityToken) {
	die('invalid security token');
}

// accept a single job ID or a comma-separated list of job IDs
$jobIds = array_map('trim', explode(',', $jobIdParam));

$i18n = I18n::getInstance($website->getConfig('supported_languages'));

foreach ($jobIds as $jobId) {
	if (strlen($jobId) === 0) {
		continue;
	}

	// get job
	$jobConfig = JobDataService::getJob($website, $db, $jobId);
	if (!$jobConfig) {
		echo 'Job config not found: ' . $jobId . "\n";
		continue;
	}

	// execute
	$jobClass = $jobConfig['class'];
	if (class_exists($jobClass)) {
		$job = new $jobClass($website, $db, $i18n, $jobId);
	} else {
		echo 'class not found: ' . $jobClass . "\n";
		continue;
	}

	$job->execute();
}
?>
