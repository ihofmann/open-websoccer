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

define('DEBUG', FALSE);

if (DEBUG) {
	error_reporting(E_ALL);
} else {
	error_reporting(E_ERROR);
}

// loads required classes on demand
function classes_autoloader($class) {
	
	$subforder = '';
	
	if (substr($class, -9) === 'Converter') {
		$subforder = 'converters/';
	} else if (substr($class, -4) === 'Skin') {
		$subforder = 'skins/';
	} else if (substr($class, -5) === 'Model') {
		$subforder = 'models/';
	} else if (substr($class, -9) === 'Validator') {
		$subforder = 'validators/';
	} else if (substr($class, -10) === 'Controller') {
		$subforder = 'actions/';
	} else if (substr($class, -7) === 'Service') {
		$subforder = 'services/';
	} else if (substr($class, -3) === 'Job') {
		$subforder = 'jobs/';
	} else if (substr($class, -11) === 'LoginMethod') {
		$subforder = 'loginmethods/';
	} else if (substr($class, -5) === 'Event') {
		$subforder = 'events/';
	} else if (substr($class, -6) === 'Plugin') {
		$subforder = 'plugins/';
	}
	
	@include(BASE_FOLDER . '/classes/' . $subforder . $class . '.class.php');
}
spl_autoload_register('classes_autoloader');

// constants
define('FOLDER_MODULES', BASE_FOLDER . '/modules');
define('MODULE_CONFIG_FILENAME', 'module.xml');
define('GLOBAL_CONFIG_FILE', BASE_FOLDER . '/generated/config.inc.php');
define('CONFIGCACHE_FILE_FRONTEND', BASE_FOLDER . '/cache/wsconfigfront.inc.php');
define('CONFIGCACHE_FILE_ADMIN', BASE_FOLDER . '/cache/wsconfigadmin.inc.php');
define('CONFIGCACHE_MESSAGES', BASE_FOLDER . '/cache/messages_%s.inc.php');
define('CONFIGCACHE_ADMINMESSAGES', BASE_FOLDER . '/cache/adminmessages_%s.inc.php');
define('CONFIGCACHE_ENTITYMESSAGES', BASE_FOLDER . '/cache/entitymessages_%s.inc.php');
define('CONFIGCACHE_SETTINGS', BASE_FOLDER . '/cache/settingsconfig.inc.php');
define('CONFIGCACHE_EVENTS', BASE_FOLDER . '/cache/eventsconfig.inc.php');
define('UPLOAD_FOLDER', BASE_FOLDER . '/uploads/');
define('IMPRINT_FILE', BASE_FOLDER . '/generated/imprint.php');
define('TEMPLATES_FOLDER', BASE_FOLDER . '/templates');
define('PROFPIC_UPLOADFOLDER', UPLOAD_FOLDER . 'users');

// dependencies
include(GLOBAL_CONFIG_FILE);
if (!isset($conf)) {
	header('location: install/index.php');
	exit;
}

$page = null;
$action = null;
$block = null;

// init application
try {
	$website = WebSoccer::getInstance();
	if (!file_exists(CONFIGCACHE_FILE_FRONTEND)) {
		$website->resetConfigCache();
	}
} catch(Exception $e) {
	// write to log
	try {
		$log = new FileWriter('errorlog.txt');
		$log->writeLine('Website Configuration Error: ' . $e->getMessage());
		$log->close();
	} catch(Exception $e) {
		// ignore
	}
	header('HTTP/1.0 500 Error');
	die();
}

// connect to DB
try {
	$db = DbConnection::getInstance();
	$db->connect($website->getConfig('db_host'),
			$website->getConfig('db_user'),
			$website->getConfig('db_passwort'),
			$website->getConfig('db_name'));
} catch(Exception $e) {
	// write to log
	try {
		$log = new FileWriter('dberrorlog.txt');
		$log->writeLine('DB Error: ' . $e->getMessage());
		$log->close();
	} catch(Exception $e) {
		// ignore
	}
	die('<h1>Sorry, our data base is currently not available</h1><p>We are working on it.</p>');
}

// register own session handler
$handler = new DbSessionManager($db, $website);
session_set_save_handler(
	array($handler, 'open'),
	array($handler, 'close'),
	array($handler, 'read'),
	array($handler, 'write'),
	array($handler, 'destroy'),
	array($handler, 'gc')
);

// the following prevents unexpected effects when using objects as save handlers
// see http://php.net/manual/en/function.session-set-save-handler.php
register_shutdown_function('session_write_close');
session_start();

// always set time zone in order to prevent PHP warnings
try {
	date_default_timezone_set($website->getConfig('time_zone'));
} catch (Exception $e) {
	// do not set time zone. This Exception can appear in particular when updating from older version.
}

?>