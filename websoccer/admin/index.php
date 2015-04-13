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
	
	echo '<li';
	if ($active) echo ' class=\'active\'';
	echo '><a href=\''. $url . '\'>'. $navLabel . '</a></li>';
}

?>
<!DOCTYPE html>
<html lang="<?php echo $i18n->getCurrentLanguage(); ?>">
  <head>
    <title><?php echo $i18n->getMessage("main_title")?></title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="bootstrap-datepicker/css/datepicker.css" rel="stylesheet">
    <link href="bootstrap-timepicker/css/bootstrap-timepicker.min.css" rel="stylesheet" >
    <link href="select2/select2.css" rel="stylesheet"/>
    <link href="markitup/skins/simple/style.css" rel="stylesheet" />
	<link href="markitup/sets/ws/style.css" rel="stylesheet" />
	<link href="bootstrap/bootstrap-tag.css" rel="stylesheet" >
	<meta charset="UTF-8">
	<link rel="shortcut icon" type="image/x-icon" href="../favicon.ico" />
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
      .sidebar-nav {
        padding: 9px 0;
      }
      
      .cupround {
			margin-left: 30px;
			border-left: 1px solid #CCCCCC;
			padding: 3px 5px 0px 10px;
		}
    </style>	
  </head>
  <body>
  
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="index.php" title="<?php echo $i18n->getMessage("admincenter_homelink_tooltip"); ?>"><?php echo $i18n->getMessage("admincenter_brand") ?></a>
          <div class="nav-collapse collapse">
            <p class="navbar-text pull-right">
              <?php echo $i18n->getMessage("admincenter_loggedin_as"); ?> <a href="?site=profile" class="navbar-link" title="<?php echo $i18n->getMessage("admincenter_editprofile_tooltip"); ?>"><?php echo escapeOutput($admin['name']); ?></a> (<a href="logout.php" class="navbar-link"><?php echo $i18n->getMessage("admincenter_logout"); ?></a>)
            </p>
            <ul class="nav">
              <li><a href="<?php 
              $contextRoot = $website->getConfig("context_root");
              echo  (strlen($contextRoot)) ? $contextRoot : "/"; ?>"><i class="icon-globe icon-white"></i> <?php echo $i18n->getMessage("admincenter_link_website"); ?></a></li>
			  <li><a href="?site=profile"><i class="icon-user icon-white"></i> <?php echo $i18n->getMessage("admincenter_link_profile"); ?></a></li>
			  <li><a href="?site=clearcache"><i class="icon-refresh icon-white"></i> <?php echo $i18n->getMessage("admincenter_link_clear_cache"); ?></a></li>
			  <li><a href="logout.php"><i class="icon-off icon-white"></i> <?php echo $i18n->getMessage("admincenter_logout"); ?></a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>  
  
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span2">
          <div class="well sidebar-nav">
            <ul class="nav nav-list">
              
			  <?php
				foreach ($navItems as $navCategory => $categoryItems) {
					echo "<li class=\"nav-header\">". $i18n->getNavigationLabel("category_" . $navCategory) . "</li>";
					foreach ($categoryItems as $navInfo) {
						printNavItem($site, $navInfo["pageid"], $navInfo["label"], $navInfo["entity"]);
					}
				}
			  ?>
            </ul>
          </div><!--/.well -->
        </div><!--/span-->
        <div class="span10">
        
        	<div id="ajaxSpinner" style="display: none">
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
	  
<!--[if lte IE 9]> 
<div class="alert">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <h4><?php echo $i18n->getMessage("internetexplorer_warning_title"); ?></h4>
  <?php echo $i18n->getMessage("internetexplorer_warning_message"); ?>
</div>
<![endif]--> 

      <footer>
        <p>Powered by <a href="http://www.websoccer-sim.com" target="_blank">OpenWebSoccer-Sim</a></p>
      </footer>		  
	</div>
	

    <script src="https://code.jquery.com/jquery-latest.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <script src="bootstrap-datepicker/js/bootstrap-datepicker.js"></script>
    <script src="bootstrap-datepicker/js/locales/bootstrap-datepicker.<?php echo $i18n->getCurrentLanguage(); ?>.js"></script>
    <script src="bootstrap-timepicker/js/bootstrap-timepicker.min.js"></script>
	<script src="select2/select2.min.js"></script>
	<?php 
	if ($i18n->getCurrentLanguage() != "en") {
		echo "<script src=\"select2/select2_locale_". $i18n->getCurrentLanguage() . ".js\"></script>";
	}
	?>
	
	<script src="markitup/jquery.markitup.js"></script>
	
	<?php if ($i18n->getCurrentLanguage() == "de") { ?>
		<script src="markitup/sets/ws/set_de.js"></script>
	<?php } else { ?>
		<script src="markitup/sets/ws/set.js"></script>
	<?php } ?>
	<script src="js/admincenter.js"></script>
	
	<script src="js/bootbox.min.js"></script>
	
	<script src="js/bootstrap-tag.js"></script>
    
	<script>
	$(function() {
		$(document).on("click", ".deleteBtn", function(e) {
			bootbox.confirm("<?php echo $i18n->getMessage("manage_delete_multiselect_confirm"); ?>", 
					"<?php echo $i18n->getMessage("option_no"); ?>",
					"<?php echo $i18n->getMessage("option_yes"); ?>",
			function(result) {
				if (result) {
					document.frmMain.submit();
				}
				
			});
		});
		$(document).on("click", ".deleteLink", function(e) {
			e.preventDefault();
	
			var link = $(this);
			
			bootbox.confirm("<?php echo $i18n->getMessage("manage_delete_link_confirm"); ?>", 
					"<?php echo $i18n->getMessage("option_no"); ?>",
					"<?php echo $i18n->getMessage("option_yes"); ?>",
			function(result) {
				if (result) {
					window.location = link.attr("href");
				}
				
			});
		});
		$(".datepicker").datepicker({
			format: "<?php echo str_replace("Y", "yyyy", $website->getConfig("date_format")); ?>",
			language: "<?php echo $i18n->getCurrentLanguage(); ?>",
			autoclose: true
		});
	});
</script>
	
  </body>
</html>