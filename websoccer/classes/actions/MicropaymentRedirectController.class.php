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
 * Redirects user to micropayment.de payment site
 *
 */
class MicropaymentRedirectController implements IActionController {
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
		
		$projectId = trim($this->_websoccer->getConfig("micropayment_project"));
		if (!strlen($projectId)) {
			throw new Exception("Configuration error: micropayment.de project ID is not specified.");
		}
		
		$accessKey = trim($this->_websoccer->getConfig("micropayment_accesskey"));
		if (!strlen($accessKey)) {
			throw new Exception("Configuration error: micropayment.de AccessKey is not specified.");
		}
		
		// collect valid modules for verification
		$validModules = array();
		if ($this->_websoccer->getConfig("micropayment_call2pay_enabled")) {
			$validModules[] = 'call2pay';
		}
		if ($this->_websoccer->getConfig("micropayment_handypay_enabled")) {
			$validModules[] = 'handypay';
		}
		if ($this->_websoccer->getConfig("micropayment_ebank2pay_enabled")) {
			$validModules[] = 'ebank2pay';
		}
		if ($this->_websoccer->getConfig("micropayment_creditcard_enabled")) {
			$validModules[] = 'creditcard';
		}
		
		// get module ID and verify it
		$module = FALSE;
		if (isset($_POST['module'])) {
			foreach ($_POST['module'] as $moduleId => $value ) {
				$module = $moduleId;
			}
		}
		if (!$module || !in_array($moduleId, $validModules)) {
			throw new Exception('Illegal payment module.');
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
		
		// micropayments expect amount in Eurocents
		if ($this->_websoccer->getConfig('premium_currency') != 'EUR') {
			throw new Exception('Configuration Error: Only payments in EUR are supported.');
		}
		$amount = $amount * 100;
		
		// construct URL
		$paymentUrl = 'https://billing.micropayment.de/' . $module . '/event/?';
		
		$parameters = array(
				'project' => $projectId,
				'amount' => $amount,
				'freeparam' => $this->_websoccer->getUser()->id
			);
		
		$queryStr = http_build_query($parameters);
		$seal = md5($parameters . $accessKey);
		
		$queryStr .= '&seal=' . $seal;
		
		$paymentUrl .= $queryStr;
		
		// redirect to payment url
		header('Location: '.$paymentUrl);
		exit;
		
		return null;
	}
	
}

?>