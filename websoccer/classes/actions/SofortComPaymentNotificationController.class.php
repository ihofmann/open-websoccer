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
require_once(BASE_FOLDER . '/lib/SofortLib-PHP-Payment-2.0.1/core/sofortLibNotification.inc.php');
require_once(BASE_FOLDER . '/lib/SofortLib-PHP-Payment-2.0.1/core/sofortLibTransactionData.inc.php');

/**
 * Verifies a payment notification call and credits premoum amount on success.
 *
 */
class SofortComPaymentNotificationController implements IActionController {
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
			throw new Exception("Sofort.com configuration key is not configured.");
		}
		
		// verify user
		$userId = $parameters['u'];
		$result = $this->_db->querySelect("id", $this->_websoccer->getConfig("db_prefix") . "_user", "id = %d", $userId);
		$user = $result->fetch_array();
		$result->free();
		if (!$user) {
			throw new Exception("illegal user id");
		}
		
		// read the notification from php://input  (http://php.net/manual/en/wrappers.php.php)
		$SofortLib_Notification = new SofortLibNotification();
		$TestNotification = $SofortLib_Notification->getNotification(file_get_contents('php://input'));
		
		// read data
		$SofortLibTransactionData = new SofortLibTransactionData($configKey);
		$SofortLibTransactionData->addTransaction($TestNotification);
		
		// verify transaction data
		$SofortLibTransactionData->sendRequest();
		if ($SofortLibTransactionData->isError()) {
			EmailHelper::sendSystemEmail($this->_websoccer, $this->_websoccer->getConfig("systememail"), 
				"Failed Sofort.com payment notification",
				"Error: " . $SofortLibTransactionData->getError());
			throw new Exception($SofortLibTransactionData->getError());
		} else {
			
			// verify status
			if ($SofortLibTransactionData->getStatus() != 'received') {
				EmailHelper::sendSystemEmail($this->_websoccer, $this->_websoccer->getConfig("systememail"),
					"Failed Sofort.com payment notification: invalid status",
					"Status: " . $SofortLibTransactionData->getStatus());
				throw new Exception("illegal status");
			}

			// credit amount
			$amount = $SofortLibTransactionData->getAmount();
			PremiumDataService::createPaymentAndCreditPremium($this->_websoccer, $this->_db, $userId, $amount, "sofortcom-notify");
		}
		
		return null;
	}
	
}

?>