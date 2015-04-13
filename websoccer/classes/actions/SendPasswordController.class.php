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

class SendPasswordController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	public function executeAction($parameters) {
		if (!$this->_websoccer->getConfig("login_allow_sendingpassword")) {
			throw new Exception("Action is disabled.");
		}
		
		// check captcha
		if ($this->_websoccer->getConfig("register_use_captcha")
				&& strlen($this->_websoccer->getConfig("register_captcha_publickey"))
				&& strlen($this->_websoccer->getConfig("register_captcha_privatekey"))) {
		
			include_once(BASE_FOLDER . "/lib/recaptcha/recaptchalib.php");
		
			$captchaResponse = recaptcha_check_answer($this->_websoccer->getConfig("register_captcha_privatekey"),
					$_SERVER["REMOTE_ADDR"],
					$_POST["recaptcha_challenge_field"],
					$_POST["recaptcha_response_field"]);
			if (!$captchaResponse->is_valid) {
				throw new Exception($this->_i18n->getMessage("registration_invalidcaptcha"));
			}
		}
		
		$email = $parameters["useremail"];
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") ."_user";
		
		// get user
		$columns = "id, passwort_salt, passwort_neu_angefordert";
		$wherePart = "UPPER(email) = '%s' AND status = 1";
		$result = $this->_db->querySelect($columns, $fromTable, $wherePart, strtoupper($email));
		$userdata = $result->fetch_array();
		$result->free();
		
		if (!isset($userdata["id"])) {
			sleep(5);
			throw new Exception($this->_i18n->getMessage("forgot-password_email-not-found"));
		}
		
		$now = $this->_websoccer->getNowAsTimestamp();
		
		$timeBoundary = $now - 24 * 3600;
		if ($userdata["passwort_neu_angefordert"] > $timeBoundary) {
			throw new Exception($this->_i18n->getMessage("forgot-password_already-sent"));
		}
		
		// create new password
		$salt = $userdata["passwort_salt"];
		if (!strlen($salt)) {
			$salt = SecurityUtil::generatePasswordSalt();
		}
		$password = SecurityUtil::generatePassword();
		$hashedPassword = SecurityUtil::hashPassword($password, $salt);
		
		// update user
		$columns = array("passwort_salt" => $salt, "passwort_neu_angefordert" => $now, "passwort_neu" => $hashedPassword);
		$whereCondition = "id = %d";
		$parameter = $userdata["id"];
		$this->_db->queryUpdate($columns, $fromTable, $whereCondition, $parameter);
		
		$this->_sendEmail($email, $password);
		
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, $this->_i18n->getMessage("forgot-password_message_title"),
				$this->_i18n->getMessage("forgot-password_message_content")));
		
		return "login";
	}
	
	private function _sendEmail($email, $password) {
		$tplparameters["newpassword"] = $password;
		
		EmailHelper::sendSystemEmailFromTemplate($this->_websoccer, $this->_i18n,
			$email,
			$this->_i18n->getMessage("sendpassword_email_subject"),
			"sendpassword",
			$tplparameters);
	}
	
}

?>