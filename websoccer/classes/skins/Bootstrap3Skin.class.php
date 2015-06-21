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
 * A simple default skin using Bootstrap 3. Can serve as base for customized skins which require Bootsrap 3.
 * 
 * @author Ingo Hofmann
 */
class Bootstrap3Skin implements ISkin {
	protected $_websoccer;
	
	/**
	 * @param WebSoccer $websoccer request context.
	 */
	public function __construct($websoccer) {
		$this->_websoccer = $websoccer;
	}
	
	/**
	 * @see ISkin::getTemplatesSubDirectory()
	 */
	public function getTemplatesSubDirectory() {
		return 'bootstrap3';
	}
	
	/**
	 * @see ISkin::getCssSources()
	 */
	public function getCssSources() {
		
		$files[] = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css';
		$files[] = '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css';
		
		$dir = $this->_websoccer->getConfig('context_root') . '/css/';
		$files[] = $dir . 'defaultskin.css';
		$files[] = $dir . 'websoccer.css';
		$files[] = $dir . 'bootstrap-responsive.min.css';
		
		return $files;
	}
	
	/**
	 * @see ISkin::getJavaScriptSources()
	 */
	public function getJavaScriptSources() {
		$dir = $this->_websoccer->getConfig('context_root') . '/js/';
		$files[] = '//code.jquery.com/jquery-1.11.3.min.js';
		
		if (DEBUG) {
			$files[] = 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js';
			$files[] = $dir . 'jquery.blockUI.js';
			$files[] = $dir . 'typeahead/typeahead.jquery.min.js';
			$files[] = $dir . 'wsbase.js';
		} else {
			$files[] = $dir . 'websoccerBootstrap3.min.js';
		}
		
		return $files;
	}
	
	/**
	 * @see ISkin::getTemplate()
	 */
	public function getTemplate($templateName) {
		return $templateName .'.twig';
	}
	
	/**
	 * @see ISkin::getImage()
	 */
	public function getImage($fileName) {
		if (file_exists(BASE_FOLDER . '/img/' . $fileName)) {
			return $this->_websoccer->getConfig('context_root') . '/img/' . $fileName;
		}
		
		return FALSE;
	}
	
	/**
	 * @return string skin name
	 */
	public function __toString() {
		return 'Bootstrap3Skin';
	}
}

?>