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
 * Enables and outputs inactivity modal popup for the admin.
 * 
 * @author Ingo Hofmann
 */
class InactivityConverter implements IConverter {
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
		if (!is_array($row)) {
			return (int)$value . '%';
		}
		
		$rate = (int)$this->_format($row['entity_user_inactivity']);
		$color = $this->_color($rate);
		$output = '<a href=\'#actPopup'. $row['id']. '\' role=\'button\' data-toggle=\'modal\' title=\''. $this->_i18n->getMessage('manage_show_details') . '\' style=\'color: '. $color .'\'>'.$rate .' %</a>';
		$output .= $this->_renderInActivityPopup($row);
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
	
	private function _color($rate) {
		if ($rate <= 10) return 'green';
		elseif ($rate <= 40) return 'black';
		elseif ($rate <= 70) return 'orange';
		else return 'red';
	}
	
	private function _renderInActivityPopup($row) {
		$popup = '';
		$popup .= '<div id=\'actPopup'. $row['id']. '\' class=\'modal hide fade\' tabindex=\'-1\' role=\'dialog\' aria-labelledby=\'actPopupLabel\' aria-hidden=\'true\'>';
		$popup .= '<div class=\'modal-header\'><button type=\'button\' class=\'close\' data-dismiss=\'modal\' aria-hidden=\'true\' title=\''. $this->_i18n->getMessage('button_close') . '\'>&times;</button>';
		$popup .= '<h3 id=\'actPopupLabel'. $row['id']. '\'>'. $this->_i18n->getMessage('entity_user_inactivity') . ': '. escapeOutput($row['entity_users_nick']) . '</h3></div>';
		$popup .= '<div class=\'modal-body\'>';
	
		$gesamt = $row['entity_user_inactivity_login'] + $row['entity_user_inactivity_aufstellung'] + $row['entity_user_inactivity_transfer']+ $row['entity_user_inactivity_vertragsauslauf'];
		$popup .= '<table class=\'table table-bordered\'>
          <thead><tr>
            <th>'. $this->_i18n->getMessage('popup_user_inactivity_title_action') . '</th>
            <th>'. $this->_i18n->getMessage('entity_user_inactivity') . '</th>
          </tr></thead>
          <tbody><tr>
            <td><b>'. $this->_i18n->getMessage('entity_user_inactivity_login') . '</b><br>
            <small>'. $this->_i18n->getMessage('entity_users_lastonline') . ': '. date('d.m.y, H:i',$row['entity_users_lastonline']) .'</small></td>
            <td style=\'text-align: center; font-weight: bold; color: '. $this->_color($this->_format($row['entity_user_inactivity_login'])) .'\'>'.$this->_format($row['entity_user_inactivity_login']).' %</td>
          </tr>
          <tr>
            <td><b>'. $this->_i18n->getMessage('entity_user_inactivity_aufstellung') . '</b></td>
            <td style=\'text-align: center; font-weight: bold; color: '.$this->_color($this->_format($row['entity_user_inactivity_aufstellung'])).'\'>'.$this->_format($row['entity_user_inactivity_aufstellung']).' %</td>
          </tr>
          <tr>
            <td><b>'. $this->_i18n->getMessage('entity_user_inactivity_transfer') . '</b><br>
            <small>'. sprintf($this->_i18n->getMessage('entity_user_inactivity_transfer_check'), date('d.m.y, H:i',$row['entity_user_inactivity_transfer_check'])) . '</small></td>
            <td style=\'text-align: center; font-weight: bold; color: '.$this->_color($this->_format($row['entity_user_inactivity_transfer'])).'\'>'.$this->_format($row['entity_user_inactivity_transfer']).' %</td>
          </tr>
          <tr>
            <td><b>'. $this->_i18n->getMessage('entity_user_inactivity_vertragsauslauf') . '</b></td>
            <td style=\'text-align: center; font-weight: bold; color: '.$this->_color($this->_format($row['entity_user_inactivity_vertragsauslauf'])).'\'>'.$this->_format($row['entity_user_inactivity_vertragsauslauf']).' %</td>
          </tr></tbody>
          <tfoot>
          <tr>
            <td><b>'. $this->_i18n->getMessage('popup_user_inactivity_total') . '</b></td>
            <td style=\'text-align: center; font-weight: bold; color: '.$this->_color($this->_format($gesamt)).'\'>'.$this->_format($gesamt).' %';
		if ($gesamt > 100) $popup .= '<br/>(' . $gesamt .'%)';
		$popup .= '</td>
          </tr>
		</tfoot>
        </table>';
	
		$popup .= '</div>';
		$popup .= '<div class=\'modal-footer\'><button class=\'btn btn-primary\' data-dismiss=\'modal\' aria-hidden=\'true\'>'. $this->_i18n->getMessage('button_close') . '</button></div>';
		$popup .= '</div>';
		return $popup;
	}	
	
	private function _format($rate) {
	
		$rate = ($rate) ? $rate : 0;
		if ($rate < 0) $rate = 0;
		elseif ($rate > 100) $rate = 100;
	
		return $rate;
	
	}
}

?>