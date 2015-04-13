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

/*
 * Starts or stops a job from config/jobs.xml.
 * Request parameters:
 * 		id: Job-ID
 * 		action: start|stop
 */

define('BASE_FOLDER', __DIR__ .'/..');

include(BASE_FOLDER . '/admin/adminglobal.inc.php');

if ($admin['r_demo']) {
	exit;
}

$jobId = $_REQUEST['id'];

$xml = simplexml_load_file(JOBS_CONFIG_FILE);
$jobConfig = $xml->xpath('//job[@id = \''. $jobId . '\']');
if (!$jobConfig) {
	throw new Exception('Job config not found.');
}

$jobClass = (string) $jobConfig[0]->attributes()->class;
if (class_exists($jobClass)) {
	$job = new $jobClass($website, $db, $i18n, $jobId, $action !== 'stop');
} else {
	throw new Exception('class not found: ' . $jobClass);
}

if ($action == 'start') {
	$job->start();
} else if ($action == 'stop') {
	$job->stop();
}

?>