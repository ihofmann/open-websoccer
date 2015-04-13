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

define('PARAM_PAGE', 'page');

// load configuration
include(CONFIGCACHE_FILE_FRONTEND);

// include messages
$i18n = I18n::getInstance($website->getConfig('supported_languages'));
$lang = $website->getRequestParameter('lang');
if ($lang) {
	try {
		$i18n->setCurrentLanguage($lang);
	} catch (Exception $e) {
		// ignore and use default language
	}
}
include(sprintf(CONFIGCACHE_MESSAGES, $i18n->getCurrentLanguage()));
include(sprintf(CONFIGCACHE_ENTITYMESSAGES, $i18n->getCurrentLanguage()));

// render RSS
$pageId = $website->getRequestParameter(PARAM_PAGE);
$website->setPageId($pageId);

header('Content-type: application/rss+xml; charset=utf-8');
$viewHandler = new ViewHandler($website, $db, $i18n, $page, $block, null);
try {
	echo $viewHandler->handlePage($pageId, array());
} catch (Exception $e) {
	echo $e->getMessage();
}
?>