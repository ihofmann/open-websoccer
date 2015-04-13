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
 * Displays a link to the match report items of a match.
 * 
 * @author Ingo Hofmann
 */
class MatchReportLinkConverter implements IConverter {
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
		$output = '<div class=\'btn-group\'>';
		$output .= '<a class=\'btn btn-small dropdown-toggle\' data-toggle=\'dropdown\' href=\'#\'>';
		$output .= $this->_i18n->getMessage('entity_match_matchreportitems') . ' <span class=\'caret\'></span>';
		$output .= '</a>';
		$output .= '<ul class=\'dropdown-menu\'>';
		
		$output .= '<li><a href=\'?site=manage-match-playerstatistics&match='. $row['id']. '\'><i class=\'icon-cog\'></i> '. $this->_i18n->getMessage('match_manage_playerstatistics') .'</a></li>';
		$output .= '<li><a href=\'?site=manage-match-reportitems&match='. $row['id']. '\'><i class=\'icon-th-list\'></i> '. $this->_i18n->getMessage('match_manage_reportitems') .'</a></li>';
		
		if (!$row['entity_match_berechnet']) {
			$output .= '<li><a href=\'?site=manage-match-complete&match='. $row['id']. '\'><i class=\'icon-ok-sign\'></i> '. $this->_i18n->getMessage('match_manage_complete') .'</a></li>';
		}
		
		$output .= '</ul>';
		$output .= '</div>';
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