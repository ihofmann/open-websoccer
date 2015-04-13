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
 * Just a dummy action controller for testing and demonstrating premium actions.
 */
class PaypalPaymentNotificationController implements IActionController {
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
		
		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		foreach ($_POST as $key => $value) {
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
		}
		// post back to PayPal system to validate
		$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
		
		$header .= "Host: ". $this->_websoccer->getConfig("paypal_host") . "\r\n";
		
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		
		$fp = fsockopen ($this->_websoccer->getConfig("paypal_url"), 443, $errno, $errstr, 30);
		if (!$fp) {
			throw new Exception("Error on HTTP(S) request. Error: " . $errno . " " . $errstr);
		} else {
			fputs ($fp, $header . $req);
			$response = "";
			while (!feof($fp)) {
				$res = fgets ($fp, 1024);
				$response .= $res;
				if (strcmp ($res, "VERIFIED") == 0) {
		
					// PAYMENT VALIDATED & VERIFIED!
					
					// check receiver e-mail
					if (strtolower($parameters["receiver_email"]) != strtolower($this->_websoccer->getConfig("paypal_receiver_email"))) {
						EmailHelper::sendSystemEmail($this->_websoccer, $this->_websoccer->getConfig("systememail"), "Failed PayPal confirmation: Invalid Receiver", 
							"Invalid receiver: " . $parameters["receiver_email"]);
						throw new Exception("Receiver E-Mail not correct.");
					}
					
					if ($parameters["payment_status"] != "Completed") {
						EmailHelper::sendSystemEmail($this->_websoccer, $this->_websoccer->getConfig("systememail"), "Failed PayPal confirmation: Invalid Status",
							"A paypment notification has been sent, but has an invalid status: " . $parameters["payment_status"]);
						throw new Exception("Payment status not correct.");
					}
						
					// credit amount to user
					$amount = $parameters["mc_gross"];
					$userId = $parameters["custom"];
					PremiumDataService::createPaymentAndCreditPremium($this->_websoccer, $this->_db, $userId, $amount, "paypal-notify");
					
					// we can exit script execution here, since action is called in background
					die(200);
		
				} else if (strcmp ($res, "INVALID") == 0) {
	
					// PAYMENT INVALID & INVESTIGATE MANUALY!
					throw new Exception("Payment is invalid");
		
				}
			}
			fclose ($fp);
				
			header('X-Error-Message: invalid paypal response: ' . $response, true, 500);
			die('X-Error-Message: invalid paypal response: ' . $response);
		}
		
		return null;
	}
	
}

?>