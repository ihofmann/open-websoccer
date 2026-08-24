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

/**
 * Validates registation form and creates a new (disabled) user in DB.
 */
class RegisterFormController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		
		// registration enabled?
		if (!$this->_websoccer->getConfig("allow_userregistration")) {
			throw new Exception($this->_i18n->getMessage("registration_disabled"));
		}
		
		// illegal user name?
		$illegalUsernames = explode(",", strtolower(str_replace(", ", ",", $this->_websoccer->getConfig("illegal_usernames"))));
		if (array_search(strtolower($parameters["nick"]), $illegalUsernames)) {
			throw new Exception($this->_i18n->getMessage("registration_illegal_username"));
		}
		
		// repeated e-mail correct?
		if ($parameters["email"] !== $parameters["email_repeat"]) {
			throw new Exception($this->_i18n->getMessage("registration_repeated_email_notmatching"));
		}
		
		// repeated password correct?
		if ($parameters["pswd"] !== $parameters["pswd_repeat"]) {
			throw new Exception($this->_i18n->getMessage("registration_repeated_password_notmatching"));
		}
		
		// check captcha
		if ($this->_websoccer->getConfig("register_use_captcha")
				&& strlen($this->_websoccer->getConfig("register_captcha_sitekey"))
				&& strlen($this->_websoccer->getConfig("register_captcha_secretkey"))) {
			if (!RecaptchaService::verify(
					$this->_websoccer->getConfig("register_captcha_secretkey"),
					$_POST["g-recaptcha-response"] ?? "",
					$_SERVER["REMOTE_ADDR"] ?? null)) {
				throw new Exception($this->_i18n->getMessage("registration_invalidcaptcha"));
			}
		}
		
		$columns = "COUNT(*) AS hits";
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_user";
		
		// check maximum number of users
		$maxNumUsers = (int) $this->_websoccer->getConfig("max_number_of_users");
		if ($maxNumUsers > 0) {
			$wherePart = "status = 1";
			$result = $this->_db->querySelect($columns, $fromTable, $wherePart);
			$rows = $result->fetch_array();
			$result->free();
			
			if ($rows["hits"] >= $maxNumUsers) {
				throw new Exception($this->_i18n->getMessage("registration_max_number_users_exceeded"));
			}
		}
		
		// Check nickname collision. Nicknames are public, so a duplicate
		// nickname can still be rejected without leaking e-mail addresses.
		$wherePart = "UPPER(nick) = '%s'";
		$result = $this->_db->querySelect($columns, $fromTable, $wherePart, array(strtoupper($parameters["nick"])));
		$rows = $result->fetch_array();
		$result->free();
		if ($rows["hits"]) {
			throw new Exception($this->_i18n->getMessage("registration_user_exists"));
		}
		
		// If the e-mail is already registered, do NOT reveal this (data privacy).
		// Instead, show the standard success state and trigger a password reset
		// in the background so the legitimate account owner is notified of the
		// attempt. Any mail delivery failure (e.g. no mail server available) is
		// silently ignored so that the user still sees the success state.
		$wherePart = "UPPER(email) = '%s'";
		$result = $this->_db->querySelect($columns, $fromTable, $wherePart, array(strtoupper($parameters["email"])));
		$rows = $result->fetch_array();
		$result->free();
		if ($rows["hits"]) {
			$this->_triggerSendPassword($parameters["email"], $fromTable);
			$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS,
					$this->_i18n->getMessage("register-success_message_title"),
					$this->_i18n->getMessage("register-success_message_content")));
			return "register-success";
		}
		
		$this->_createUser($parameters, $fromTable);
		
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, $this->_i18n->getMessage("register-success_message_title"), 
				$this->_i18n->getMessage("register-success_message_content")));
		
		return "register-success";
	}
	
	/**
	 * Triggers a password reset for the owner of an already registered e-mail
	 * address, without revealing that the address is in use. Any failure (e.g.
	 * no mail server available, or the account is not in an active state) is
	 * silently ignored so that the registration always reports success.
	 *
	 * @param string $email e-mail address entered in the registration form.
	 * @param string $fromTable fully qualified user table name.
	 */
	private function _triggerSendPassword($email, $fromTable) {
		try {
			$columns = "id, passwort_salt, passwort_neu_angefordert";
			$wherePart = "UPPER(email) = '%s' AND status = 1";
			$result = $this->_db->querySelect($columns, $fromTable, $wherePart, strtoupper($email));
			$userdata = $result->fetch_array();
			$result->free();
			
			if (!isset($userdata["id"])) {
				return;
			}
			
			$now = $this->_websoccer->getNowAsTimestamp();
			$timeBoundary = $now - 24 * 3600;
			if ($userdata["passwort_neu_angefordert"] > $timeBoundary) {
				return;
			}
			
			$salt = $userdata["passwort_salt"];
			if (!strlen($salt)) {
				$salt = SecurityUtil::generatePasswordSalt();
			}
			$password = SecurityUtil::generatePassword();
			$hashedPassword = SecurityUtil::hashPassword($password, $salt);
			
			$updateColumns = array("passwort_salt" => $salt,
					"passwort_neu_angefordert" => $now, "passwort_neu" => $hashedPassword);
			$this->_db->queryUpdate($updateColumns, $fromTable, "id = %d", $userdata["id"]);
			
			$tplparameters["newpassword"] = $password;
			EmailHelper::sendSystemEmailFromTemplate($this->_websoccer, $this->_i18n,
				strtolower($email),
				$this->_i18n->getMessage("sendpassword_email_subject"),
				"sendpassword",
				$tplparameters);
		} catch (Exception $e) {
			// Ignore any failure (e.g. no mail server). The user still sees
			// the standard registration success state.
		}
	}
	
	private function _createUser($parameters, $fromTable) {
		$dbcolumns = array();
		
		$dbcolumns["nick"] = $parameters["nick"];
		$dbcolumns["email"] = strtolower($parameters["email"]);
		$dbcolumns["passwort_salt"] = SecurityUtil::generatePasswordSalt();
		$dbcolumns["passwort"] = SecurityUtil::hashPassword($parameters["pswd"], $dbcolumns["passwort_salt"]);
		$dbcolumns["datum_anmeldung"] = $this->_websoccer->getNowAsTimestamp();
		$dbcolumns["schluessel"] = str_replace("&", "_", SecurityUtil::generatePassword());
		$dbcolumns["status"] = 2;
		$dbcolumns["lang"] = $this->_i18n->getCurrentLanguage();
		
		if ($this->_websoccer->getConfig("premium_initial_credit")) {
			$dbcolumns["premium_balance"] = $this->_websoccer->getConfig("premium_initial_credit");
		}
		
		$this->_db->queryInsert($dbcolumns, $fromTable);
		
		// get user id
		$columns = "id";
		$wherePart = "email = '%s'";
		$result = $this->_db->querySelect($columns, $fromTable, $wherePart, $dbcolumns["email"]);
		$newuser = $result->fetch_array();
		$result->free();
		
		$querystr = "key=" . $dbcolumns["schluessel"] ."&userid=" . $newuser["id"];
		$tplparameters["activationlink"] = $this->_websoccer->getInternalActionUrl("activate", $querystr, "activate-user", TRUE);
		
		// send e-mail
		EmailHelper::sendSystemEmailFromTemplate($this->_websoccer, $this->_i18n, 
			$dbcolumns["email"], 
			$this->_i18n->getMessage("activation_email_subject"), 
			"useractivation", 
			$tplparameters);
		
		// trigger plug-ins
		$event = new UserRegisteredEvent($this->_websoccer, $this->_db, $this->_i18n, 
				$newuser["id"], $dbcolumns["nick"], $dbcolumns["email"]);
		PluginMediator::dispatchEvent($event);
	}
}

?>