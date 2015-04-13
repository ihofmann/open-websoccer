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
 * Displays a link which enables approval of  transfer offers in the admin center.
 * 
 * @author Ingo Hofmann
 */
class TransferOfferApprovalLinkConverter implements IConverter {
	private $_i18n;
	private $_websoccer;
	
	public function __construct($i18n, $websoccer) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * @see IConverter::toHtml()
	 */
	public function toHtml($row) {
		if ($row['entity_transfer_offer_admin_approval_pending']) {
			$output = ' <a href=\'?site=manage&entity=transfer_offer&action=transferofferapprove&id='. $row['id']. '\' class=\'btn btn-small btn-success\'><i class=\'icon-ok icon-white\'></i> '. $this->_i18n->getMessage('button_approve') .'</a>';
		} else {
			$output = '<i class=\'icon-ban-circle\'></i>';
		}
		
		return $output;
	}
	
	/**
	 * @see IConverter::toText()
	 */
	public function toText($value) {
		return $value;
	}
	
	/**
	 * @see IConverter::toDbValue()
	 */
	public function toDbValue($value) {
		return $this->toText($value);
	}
	
}

?>