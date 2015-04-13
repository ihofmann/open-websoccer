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
define('BASE_FOLDER', '../');
require_once('config/global.inc.php');
require_once('functions.inc.php');

// include messages
$i18n = I18n::getInstance($website->getConfig('supported_languages'));

if (isset($_GET['lang'])) {
	$i18n->setCurrentLanguage($_GET['lang']);
}

include(sprintf(CONFIGCACHE_ADMINMESSAGES, $i18n->getCurrentLanguage()));


$errors = array();
$inputEmail = (isset($_POST['inputEmail'])) ? trim($_POST['inputEmail']) : FALSE;

// process form
if ($inputEmail) {
	
	$now = $website->getNowAsTimestamp();
	
	if (count($errors) == 0) {
		
		// correct Pwd?
		$columns = array('id', 'passwort_neu_angefordert', 'name', 'passwort_salt');
		$fromTable = $conf['db_prefix'] .'_admin';
		$whereCondition = 'email = \'%s\'';
		$parameters = $inputEmail;
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		$admin = $result->fetch_array();
		
		if($result->num_rows < 1) {
			$errors['inputEmail'] = $i18n->getMessage('sendpassword_admin_usernotfound');
		} elseif ($admin['passwort_neu_angefordert'] > ($now-120*60)) {
			$errors['inputEmail'] = $i18n->getMessage('sendpassword_admin_alreadysent');
		} else {
			$newPassword = SecurityUtil::generatePassword();
			$hashedPw = SecurityUtil::hashPassword($newPassword, $admin['passwort_salt']);
			
			// store new PW
			$columns = array('passwort_neu' => $hashedPw, 
							'passwort_neu_angefordert' => $now);
			$fromTable = $conf['db_prefix'] .'_admin';
			$whereCondition = 'id = %d';
			$parameter = $admin['id'];
			$db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);

            try {
            	_sendEmail($inputEmail, $newPassword, $website, $i18n);
            	
            	header('location: login.php?newpwd=1');
            	die();
            } catch(Exception $e) {
            	$errors['inputEmail'] = $e->getMessage();
            }
		
		}
		$result->free();
		
	}
}

function _sendEmail($email, $password, $website, $i18n) {
	$tplparameters['newpassword'] = $password;

	EmailHelper::sendSystemEmailFromTemplate($website, $i18n,
		$email,
		$i18n->getMessage('sendpassword_admin_email_subject'),
		'sendpassword_admin',
		$tplparameters);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>AdminCenter - <?php echo $i18n->getMessage('sendpassword_admin_title'); ?></title>
    <link href='bootstrap/css/bootstrap.min.css' rel='stylesheet' media='screen'>
    <meta charset='UTF-8'>
    <link rel='shortcut icon' type='image/x-icon' href='../favicon.ico' />
    <style type='text/css'>
      body {
        padding-top: 100px;
        padding-bottom: 40px;
      }
    </style>
  </head>
  <body>
  
	<div class='container'>
	
		<h1><?php echo $i18n->getMessage('sendpassword_admin_title'); ?></h1>
		
<?php
if (count($errors) > 0) {
	foreach($errors as $key => $message) {
		echo createErrorMessage($i18n->getMessage('subpage_error_title'), $message);
	}
	
}
?>
		<p><?php echo $i18n->getMessage('sendpassword_admin_intro'); ?></p>
		<form action='forgot-password.php' method='post' class='form-horizontal'>
		  <div class='control-group<?php if (isset($errors['inputEmail'])) echo ' error'; ?>'>
			<label class='control-label' for='inputEmail'><?php echo $i18n->getMessage('sendpassword_admin_label_email'); ?></label>
			<div class='controls'>
			  <input type='email' name='inputEmail' id='inputEmail' placeholder='E-Mail' value='<?php echo escapeOutput($inputEmail); ?>'>
			</div>
		  </div>
		  <div class='control-group'>
			<div class='controls'>
			  <button type='submit' class='btn'><?php echo $i18n->getMessage('sendpassword_admin_button'); ?></button>
			</div>
		  </div>
		</form>		
		
		<p><a href='login.php'><?php echo $i18n->getMessage('sendpassword_admin_loginlink'); ?></a>
	  
      <hr>

      <footer>
        <p>Powered by <a href='http://www.websoccer-sim.com' target='_blank'>OpenWebSoccer-Sim</a></p>
      </footer>		  
	</div>
	

    <script src='https://code.jquery.com/jquery-latest.min.js'></script>
    <script src='bootstrap/js/bootstrap.min.js'></script>
  </body>
</html>