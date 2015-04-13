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

define('OVERVIEW_SITE_SUFFIX', '_overview');
define('JOBS_CONFIG_FILE', BASE_FOLDER . '/admin/config/jobs.xml');
define('LOG_TYPE_EDIT', 'edit');
define('LOG_TYPE_DELETE', 'delete');

include(BASE_FOLDER . '/admin/config/global.inc.php');
include(BASE_FOLDER . '/admin/functions.inc.php');
include(CONFIGCACHE_FILE_ADMIN);

// request parameters
$site = (isset($_REQUEST['site'])) ? $_REQUEST['site'] : '';
$show = (isset($_REQUEST['show'])) ? $_REQUEST['show'] : FALSE;
$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;

// log in user
if (SecurityUtil::isAdminLoggedIn()) {
	$columns = '*';
	$fromTable = $conf['db_prefix'] .'_admin';
	$whereCondition = 'id = %d';
	$parameters = $_SESSION['userid'];
	$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
	$admin = $result->fetch_array();
	$result->free();
} else {
	header('location: login.php?forwarded=1');
	exit;
}

// include messages
$i18n = I18n::getInstance($website->getConfig('supported_languages'));
if ($admin['lang']) {
	try {
		$i18n->setCurrentLanguage($admin['lang']);
	} catch (Exception $e) {
		// ignore and use default language
	}
}
include(sprintf(CONFIGCACHE_ADMINMESSAGES, $i18n->getCurrentLanguage()));
include(sprintf(CONFIGCACHE_ENTITYMESSAGES, $i18n->getCurrentLanguage()));

header('Content-type: text/html; charset=utf-8');