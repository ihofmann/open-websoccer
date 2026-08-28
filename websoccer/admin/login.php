<?php

/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it 
  and/or modify it under the terms of the 
  GNU Lesser General Public License 
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public 
  License along with OpenWebSoccer-Sim.  
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/

/**
 * Maximum number of failed verification-code attempts before the
 * account is temporarily blocked.
 */
define('ADMIN_2FA_MAX_ATTEMPTS', 3);

/**
 * Number of seconds the account stays blocked after too many failed
 * verification attempts.
 */
define('ADMIN_2FA_BLOCK_DURATION', 5 * 60);

define('BASE_FOLDER', __DIR__ .'/..');
include(BASE_FOLDER . '/admin/config/global.inc.php');
include(BASE_FOLDER . '/admin/functions.inc.php');

include(CONFIGCACHE_FILE_ADMIN);

// include messages
$i18n = I18n::getInstance($website->getConfig('supported_languages'));

if (isset($_GET['lang'])) {
	$i18n->setCurrentLanguage($_GET['lang']);
}

include(sprintf(CONFIGCACHE_ADMINMESSAGES, $i18n->getCurrentLanguage()));

$errors = array();
$showVerificationForm = FALSE;
$displayedCode = NULL;
$blockedError = NULL;

$inputUser = (isset($_POST['inputUser'])) ? $_POST['inputUser'] : FALSE;
$inputPassword = (isset($_POST['inputPassword'])) ? $_POST['inputPassword'] : FALSE;
$inputVerificationCode = (isset($_POST['inputVerificationCode'])) ? trim($_POST['inputVerificationCode']) : FALSE;
$forwarded = (isset($_GET['forwarded']) && $_GET['forwarded'] == 1) ? TRUE : FALSE;
$loggedout = (isset($_GET['loggedout']) && $_GET['loggedout'] == 1) ? TRUE : FALSE;
$newpwd = (isset($_GET['newpwd']) && $_GET['newpwd'] == 1) ? TRUE : FALSE; 

$now = $website->getNowAsTimestamp();

// ---------------------------------------------------------------------------
// Step 2: Verification code submission
// ---------------------------------------------------------------------------
if ($inputVerificationCode !== FALSE) {
	$pendingAdminId = (isset($_SESSION['pending_2fa_admin_id'])) ? $_SESSION['pending_2fa_admin_id'] : FALSE;

	if (!$pendingAdminId) {
		header('location: login.php');
		die();
	}

	// load admin with 2FA columns
	$columns = array('id', 'name', 'passwort', 'passwort_salt', 'passwort_neu',
		'verification_code', 'login_attempts', 'blocked_until');
	$fromTable = $conf['db_prefix'] .'_admin';
	$whereCondition = 'id = %d';
	$parameters = $pendingAdminId;
	$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
	$admin = $result->fetch_array();
	$result->free();

	if (!$admin) {
		unset($_SESSION['pending_2fa_admin_id']);
		header('location: login.php');
		die();
	}

	// check if still blocked
	if ($admin['blocked_until'] > $now) {
		$errors['inputVerificationCode'] = $i18n->getMessage('login_2fa_error_blocked');
		$showVerificationForm = TRUE;
	} elseif ($inputVerificationCode === '') {
		$errors['inputVerificationCode'] = $i18n->getMessage('login_2fa_error_nocode');
		$showVerificationForm = TRUE;
	} else {
		// compare code
		if ($inputVerificationCode === $admin['verification_code']) {
			// correct code - complete login
			$hashedPw = (isset($_SESSION['pending_2fa_hashed_pw'])) ? $_SESSION['pending_2fa_hashed_pw'] : '';

			if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
				session_destroy();
				session_start();
			} elseif (version_compare(PHP_VERSION, '5.4.0') >= 0) {
				session_regenerate_id();
			}
			$_SESSION['valid'] = 1;
			$_SESSION['userid'] = $admin['id'];

			// update new PW (activate new password if it was used)
			$updateColumns = array(
				'verification_code' => '',
				'login_attempts' => 0,
				'blocked_until' => 0
			);
			if ($admin['passwort_neu'] && $admin['passwort_neu'] == $hashedPw) {
				$updateColumns['passwort'] = $hashedPw;
				$updateColumns['passwort_neu_angefordert'] = 0;
				$updateColumns['passwort_neu'] = '';
			}
			$db->queryUpdate($updateColumns, $conf['db_prefix'] .'_admin', 'id = %d', $admin['id']);

			// write log
			if ($admin['name']) {
				$ip = getenv('REMOTE_ADDR');
				AdminLogDataService::create($website, $db, $admin['name'], $ip, $now);
			}

			header('location: index.php');
			die();
		} else {
			// wrong code - increment attempts
			$attempts = (int)$admin['login_attempts'] + 1;

			if ($attempts >= ADMIN_2FA_MAX_ATTEMPTS) {
				// block for 5 minutes
				$updateColumns = array(
					'login_attempts' => $attempts,
					'blocked_until' => $now + ADMIN_2FA_BLOCK_DURATION
				);
				$db->queryUpdate($updateColumns, $conf['db_prefix'] .'_admin', 'id = %d', $admin['id']);

				$errors['inputVerificationCode'] = $i18n->getMessage('login_2fa_error_blocked');
			} else {
				$db->queryUpdate(array('login_attempts' => $attempts),
					$conf['db_prefix'] .'_admin', 'id = %d', $admin['id']);

				$errors['inputVerificationCode'] = $i18n->getMessage('login_2fa_error_wrong_code');
			}
			$showVerificationForm = TRUE;
		}
	}
}

// ---------------------------------------------------------------------------
// Step 1: Credentials submission
// ---------------------------------------------------------------------------
if (($inputUser or $inputPassword) && $inputVerificationCode === FALSE) {
	if (!$inputUser) {
		$errors['inputUser'] = $i18n->getMessage('login_error_nousername');
	}
	if (!$inputPassword) {
		$errors['inputPassword'] = $i18n->getMessage('login_error_nopassword');
	}	
	
	if (count($errors) == 0) {
		
		// correct Pwd?
		$columns = array('id', 'passwort', 'passwort_salt', 'passwort_neu', 'name',
			'email', 'verification_code', 'login_attempts', 'blocked_until');
		$fromTable = $conf['db_prefix'] .'_admin';
		$whereCondition = 'name = \'%s\'';
		$parameters = $inputUser;
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $parameters);
		
		
		if($result->num_rows < 1) {
			$errors['inputUser'] = $i18n->getMessage('login_error_unknownusername');
		} else {
			$admin = $result->fetch_array();
			
			$hashedPw = SecurityUtil::hashPassword($inputPassword, $admin['passwort_salt']);
			if ($admin['passwort'] == $hashedPw || $admin['passwort_neu'] == $hashedPw) {

				// check if account is blocked
				if ($admin['blocked_until'] > $now) {
					$blockedError = $i18n->getMessage('login_2fa_error_blocked');
					sleep(5);
				} else {
					// generate 6-digit verification code
					$verificationCode = sprintf('%06d', random_int(0, 999999));

					// store code and reset attempts
					$db->queryUpdate(
						array(
							'verification_code' => $verificationCode,
							'login_attempts' => 0,
							'blocked_until' => 0
						),
						$conf['db_prefix'] .'_admin',
						'id = %d',
						$admin['id']
					);

					// send code via e-mail
					try {
						$subject = sprintf($i18n->getMessage('login_2fa_email_subject'),
							$website->getConfig('projectname'), $verificationCode);
						EmailHelper::sendSystemEmail($website, $admin['email'], $subject, $verificationCode);
					} catch (Exception $e) {
						$displayedCode = $verificationCode;
					}

					// store pending admin ID and hashed password in session for step 2
					$_SESSION['pending_2fa_admin_id'] = $admin['id'];
					$_SESSION['pending_2fa_hashed_pw'] = $hashedPw;

					$showVerificationForm = TRUE;
				}
			} else {
				$errors['inputPassword'] = $i18n->getMessage('login_error_invalidpassword');
				sleep(5);
			}
		
		}
		$result->free();
		
	}
}

header('Content-type: text/html; charset=utf-8');
sendAdminSecurityHeaders();
?>
<!DOCTYPE html>
<html lang='de'>
  <head>
    <title><?php echo $i18n->getMessage('login_title');?></title>
    <link href='../assets/admincenter.css' rel='stylesheet' media='screen'>
    <link rel='shortcut icon' type='image/x-icon' href='../favicon.ico' />
    <meta charset='UTF-8'>
  </head>
  <body class='admin-login'>
  
	<div class='container'>
	
		<h1><?php echo $i18n->getMessage('login_title');?></h1>
		
<?php
if ($showVerificationForm) {
	// Verification step (step 2)
?>
		<h2><?php echo $i18n->getMessage('login_2fa_title'); ?></h2>

<?php
	if ($displayedCode !== NULL) {
		echo createWarningMessage(
			$i18n->getMessage('login_2fa_email_not_sent_title'),
			sprintf($i18n->getMessage('login_2fa_email_not_sent'), escapeOutput($displayedCode))
		);
	} else {
		echo createInfoMessage(
			$i18n->getMessage('login_2fa_email_sent_title'),
			$i18n->getMessage('login_2fa_email_sent')
		);
	}

	if (isset($errors['inputVerificationCode'])) {
		echo createErrorMessage($i18n->getMessage('login_alert_error_title'), $errors['inputVerificationCode']);
	}
?>

		<p><a href='?lang=en'>English</a> | <a href='?lang=de'>Deutsch</a></p>

		<form action='login.php' method='post' class='row'>
		  <div class='mb-3<?php if (isset($errors['inputVerificationCode'])) echo ' error'; ?>'>
			<label class='form-label' for='inputVerificationCode'><?php echo $i18n->getMessage('login_2fa_label_code');?></label>
			<div>
			  <input type='text' class='form-control' name='inputVerificationCode' id='inputVerificationCode' placeholder='<?php echo $i18n->getMessage('login_2fa_label_code');?>' inputmode='numeric' pattern='[0-9]{6}' maxlength='6' required autofocus>
			</div>
		  </div>
		  <div class='mb-3'>
			<div>
			  <button type='submit' class='btn btn-primary'><?php echo $i18n->getMessage('login_2fa_button_verify');?></button>
			</div>
		  </div>
		</form>

		<p><a href='login.php'><?php echo $i18n->getMessage('login_2fa_link_back'); ?></a></p>

<?php
} else {
	// Credentials step (step 1)
	if ($blockedError) {
		echo createErrorMessage($i18n->getMessage('login_alert_error_title'), $blockedError);
	} else if ($forwarded) {
		echo createWarningMessage($i18n->getMessage('login_alert_accessdenied_title'), $i18n->getMessage('login_alert_accessdenied_content'));
	} else if ($loggedout) {
		echo createSuccessMessage($i18n->getMessage('login_alert_logoutsuccess_title'), $i18n->getMessage('login_alert_logoutsuccess_content'));
	} else if ($newpwd) {
		echo createSuccessMessage($i18n->getMessage('login_alert_sentpassword_title'), $i18n->getMessage('login_alert_sentpassword_content'));
	} else if (count($errors) > 0) {
		echo createErrorMessage($i18n->getMessage('login_alert_error_title'), $i18n->getMessage('login_alert_error_content'));
	}
?>

		<p><a href='?lang=en'>English</a> | <a href='?lang=de'>Deutsch</a></p>
		
		<form action='login.php' method='post' class='row'>
		  <div class='mb-3<?php if (isset($errors['inputUser'])) echo ' error'; ?>'>
			<label class='form-label' for='inputUser'><?php echo $i18n->getMessage('login_label_user');?></label>
			<div>
			  <input type='text' class='form-control' name='inputUser' id='inputUser' placeholder='<?php echo $i18n->getMessage('login_label_user');?>' required>
			</div>
		  </div>
		  <div class='mb-3<?php if (isset($errors['inputPassword'])) echo ' error'; ?>'>
			<label class='form-label' for='inputPassword'><?php echo $i18n->getMessage('login_label_password');?></label>
			<div>
			  <input type='password' class='form-control' name='inputPassword' id='inputPassword' placeholder='<?php echo $i18n->getMessage('login_label_password');?>' required>
			</div>
		  </div>
		  <div class='mb-3'>
			<div>
			  <button type='submit' class='btn btn-primary'><?php echo $i18n->getMessage('login_button_logon');?></button>
			</div>
		  </div>
		</form>		
		
		<p><a href='forgot-password.php'><?php echo $i18n->getMessage('login_link_forgotpassword');?></a>
<?php
}
?>
	  
      <hr>

      <footer>
        <p>Powered by <a href='http://www.websoccer-sim.com' target='_blank'>OpenWebSoccer-Sim</a></p>
      </footer>		  
	</div>
	

    <script src='../assets/admincenter.js'></script>
  </body>
</html>
