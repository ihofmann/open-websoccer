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
 * Provides the pyment link to PayPal.
 */
class PaypalLinkModel implements IModel {
	private $_db;
	private $_i18n;
	private $_websoccer;

	public function __construct($db, $i18n, $websoccer) {
		$this->_db = $db;
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}

	/**
	 * (non-PHPdoc)
	 * @see IModel::renderView()
	 */
	public function renderView() {
		return $this->_websoccer->getConfig("paypal_enabled");
	}

	/**
	 * (non-PHPdoc)
	 * @see IModel::getTemplateParameters()
	 */
	public function getTemplateParameters() {

		$userId = $this->_websoccer->getUser()->id;
		$linkCode = $this->_websoccer->getConfig("paypal_buttonhtml");

		$customField = "<input type=\"hidden\" name=\"custom\" value=\"". $userId . "\">";
		$notifyUrlField = "<input type=\"hidden\" name=\"notify_url\" value=\"". $this->_websoccer->getInternalActionUrl("paypal-notify", null, null, TRUE) . "\">";
		
		$linkCode = str_replace("</form>",
				$notifyUrlField . $customField . "</form>", $linkCode);

		return array("linkCode" => $linkCode);
	}

}

?>