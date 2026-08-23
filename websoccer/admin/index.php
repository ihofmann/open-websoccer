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

include(BASE_FOLDER . '/admin/adminglobal.inc.php');

// building nav
$navItems['settings'] = array();
$navItems['website'] = array();

foreach ($adminpage as $pageId => $pageData) {
	$pageInfo = json_decode($pageData, true);

	// check permission
	if ((!isset($admin['r_admin']) || !$admin['r_admin']) && (!isset($admin['r_demo']) || !$admin['r_demo'])
			&& (!isset($admin[$pageInfo['permissionrole']]) || !$admin[$pageInfo['permissionrole']])) {
		continue;
	}

	if (isset($pageInfo['entity']) && $pageInfo['entity']) {
		$siteInfo['label'] = $i18n->getMessage('entity_' . $pageInfo['entity']);
		$siteInfo['pageid'] = 'manage';
		$siteInfo['entity'] = $pageInfo['entity'];
	} else {
		$siteInfo['label'] = $i18n->getNavigationLabel($pageId);
		$siteInfo['pageid'] = $pageInfo['filename'];
		$siteInfo['entity'] = null;
	}

	$navItems[$pageInfo['navcategory']][] = $siteInfo;
}

function printNavItem($currentSite, $pageId, $navLabel, $entity = '') {

	$url = '?site='. $pageId;
	$active = ($currentSite == $pageId);

	if (strlen($entity)) {
		$url .= '&entity=' . escapeOutput($entity);
		$active = (isset($_REQUEST['entity']) &&  $_REQUEST['entity'] == $entity);
	}

	echo '<li class="nav-item">';
	echo '<a class="nav-link';
	if ($active) echo ' active';
	echo '" href=\''. $url . '\'>'. $navLabel . '</a></li>';
}

?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->getCurrentLanguage(); ?>">
  <head>
    <title><?php echo $i18n->getMessage("main_title")?></title>
    <link href="../assets/admincenter.css" rel="stylesheet" media="screen">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico" />
  </head>
  <body class="admin-main"
        data-delete-multiselect-confirm="<?php echo escapeOutput($i18n->getMessage('manage_delete_multiselect_confirm')); ?>"
        data-delete-link-confirm="<?php echo escapeOutput($i18n->getMessage('manage_delete_link_confirm')); ?>"
        data-option-no="<?php echo escapeOutput($i18n->getMessage('option_no')); ?>"
        data-option-yes="<?php echo escapeOutput($i18n->getMessage('option_yes')); ?>">

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top bg-dark">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand" href="index.php" title="<?php echo $i18n->getMessage("admincenter_homelink_tooltip"); ?>"><?php echo $i18n->getMessage("admincenter_brand") ?></a>
        <div class="collapse navbar-collapse" id="adminNavbar">
          <ul class="navbar-nav me-auto">
              <li class="nav-item"><a class="nav-link" href="<?php
              $contextRoot = $website->getConfig("context_root");
              echo  (strlen($contextRoot)) ? $contextRoot : "/"; ?>"><i class="bi bi-globe"></i> <?php echo $i18n->getMessage("admincenter_link_website"); ?></a></li>
			  <li class="nav-item"><a class="nav-link" href="?site=profile"><i class="bi bi-person"></i> <?php echo $i18n->getMessage("admincenter_link_profile"); ?></a></li>
			  <li class="nav-item"><a class="nav-link" href="?site=clearcache"><i class="bi bi-arrow-clockwise"></i> <?php echo $i18n->getMessage("admincenter_link_clear_cache"); ?></a></li>
			  <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-power"></i> <?php echo $i18n->getMessage("admincenter_logout"); ?></a></li>
          </ul>
          <p class="navbar-text">
              <?php echo $i18n->getMessage("admincenter_loggedin_as"); ?> <a href="?site=profile" class="navbar-link" title="<?php echo $i18n->getMessage("admincenter_editprofile_tooltip"); ?>"><?php echo escapeOutput($admin['name']); ?></a> (<a href="logout.php" class="navbar-link"><?php echo $i18n->getMessage("admincenter_logout"); ?></a>)
            </p>
        </div><!--/.navbar-collapse -->
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <div class="col-md-4 col-lg-3 py-3">
          <div class="card sidebar-nav">
            <ul class="nav flex-column nav-pills gap-1">

			  <?php
				foreach ($navItems as $navCategory => $categoryItems) {
					echo "<li class=\"nav-item\"><span class=\"nav-link fw-bold text-secondary\">". $i18n->getNavigationLabel("category_" . $navCategory) . "</span></li>";
					foreach ($categoryItems as $navInfo) {
						printNavItem($site, $navInfo["pageid"], $navInfo["label"], $navInfo["entity"]);
					}
				}
			  ?>
            </ul>
          </div><!--/.card -->
        </div><!--/span-->
        <div class="col-md-8 col-lg-9 p-3">

        	<div id="ajaxSpinner" class="ws-hidden">
        		<img src="../img/ajax-loader.gif" width="16" height="16" />
        	</div>
<?php
if (empty($site)) {
	$site = 'home';
}

$includeFile = 'pages/' . $site .'.php';
if (preg_match('#^[a-z0-9_-]+$#i', $site) && file_exists($includeFile) ) {
	try {
		include( $includeFile );
	} catch(Exception $e) {
		echo createErrorMessage($i18n->getMessage('alert_error_title'), $e->getMessage());
	}
} else {
	echo createErrorMessage($i18n->getMessage('alert_error_title'), $i18n->getMessage('error_page_not_found'));
}
?>
        </div><!--/span-->
      </div><!--/row-->

      <hr>

      <footer>
        <p>Powered by <a href="http://www.websoccer-sim.com" target="_blank">OpenWebSoccer-Sim</a></p>
      </footer>
	</div>


    <script src="../assets/admincenter.js"></script>

  </body>
</html>
