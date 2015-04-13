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
 * Same as DefaultBootstrapSkin, but with green colors.
 * 
 * @author Ingo Hofmann
 */
class GreenBootstrapSkin extends DefaultBootstrapSkin {
	
	/**
	 * @see DefaultBootstrapSkin::getCssSources()
	 */
	public function getCssSources() {
		$dir = $this->_websoccer->getConfig('context_root') . '/css/';
		$files[] = $dir . 'bootstrap_green.css';
		$files[] = $dir . 'websoccer.css';
		$files[] = $dir . 'bootstrap-responsive.min.css';
		
		$files[] = '//netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css';
	
		if ($this->_websoccer->getPageId() == 'formation'
				|| $this->_websoccer->getPageId() == 'training') {
			$files[] = $dir . 'slider.css';
		}
	
		if ($this->_websoccer->getPageId() == 'formation'
				|| $this->_websoccer->getPageId() == 'youth-formation'
				|| $this->_websoccer->getPageId() == 'teamoftheday') {
			$files[] = $dir . 'formation.css';
			$files[] = $dir . 'bootstrap-switch.css';
		}
		
		return $files;
	}
	
}
?>