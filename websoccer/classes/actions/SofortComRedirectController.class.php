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
require_once(BASE_FOLDER . '/lib/SofortLib-PHP-Payment-2.0.1/payment/sofortLibSofortueberweisung.inc.php');

/**
 * Redirects user to Sofort.com payment site
 *
 */
class SofortComRedirectController implements IActionController {
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
		
		$configKey = trim($this->_websoccer->getConfig("sofortcom_configkey"));
		
		if (!strlen($configKey)) {
			// user should actually not come here, hence no i18n
			throw new Exception("Sofort.com configuration key is not configured.");
		}
		
		// verify amount (check if specified in options)
		$amount = $parameters['amount'];
		
		$priceOptions = explode(',', $this->_websoccer->getConfig('premium_price_options'));
		$validAmount = FALSE;
		if (count($priceOptions)) {
			foreach ($priceOptions as $priceOption) {
				$optionParts = explode(':', $priceOption);
		
				$realMoney = trim($optionParts[0]);
		
				// credit amount and end here
				if ($amount == $realMoney) {
					$validAmount = TRUE;
				}
			}
		}
		if (!$validAmount) {
			// amount comes actually from a selection list, hence can be invalid only by cheating -> no i18n
			throw new Exception("Invalid amount");
		}
		
		// create transaction model
		$Sofortueberweisung = new Sofortueberweisung($configKey);
		
		$abortOrSuccessUrl = $this->_websoccer->getInternalUrl('premiumaccount', null, TRUE);
		
		// use actual notify url
		$notifyUrl = $this->_websoccer->getInternalActionUrl('sofortcom-notify', 'u=' . $this->_websoccer->getUser()->id, 
				'home', TRUE);
		
		$Sofortueberweisung->setAmount($amount);
		$Sofortueberweisung->setCurrencyCode($this->_websoccer->getConfig("premium_currency"));
		$Sofortueberweisung->setReason($this->_websoccer->getConfig("projectname"));
		$Sofortueberweisung->setSuccessUrl($abortOrSuccessUrl, true);
		$Sofortueberweisung->setAbortUrl($abortOrSuccessUrl);
		$Sofortueberweisung->setNotificationUrl($notifyUrl, 'received');
		
		$Sofortueberweisung->sendRequest();
		
		if ($Sofortueberweisung->isError()) {
			throw new Exception($Sofortueberweisung->getError());
		} else {
			// redirect to payment url
			$paymentUrl = $Sofortueberweisung->getPaymentUrl();
			header('Location: '.$paymentUrl);
			exit;
		}
		
		return null;
	}
	
}

?>