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

define('JOBS_CONFIG_FILE', BASE_FOLDER . '/admin/config/jobs.xml');

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
$jobId = $_REQUEST['jobid'];

// check security token
if ($website->getConfig('webjobexecution_key') !== $securityToken) {
	die('invalid security token');
}

// get job
$xml = simplexml_load_file(JOBS_CONFIG_FILE);
$jobConfig = $xml->xpath('//job[@id = \''. $jobId . '\']');
if (!$jobConfig) {
	die('Job config not found.');
}

// execute
$jobClass = (string) $jobConfig[0]->attributes()->class;
if (class_exists($jobClass)) {
	
	$i18n = I18n::getInstance($website->getConfig('supported_languages'));
	$job = new $jobClass($website, $db, $i18n, $jobId);
	
} else {
	die('class not found: ' . $jobClass);
}

$job->execute();
?>