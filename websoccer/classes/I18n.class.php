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

define('PAGE_NAV_LABEL_SUFFIX', '_navlabel');
define('LANG_SESSION_PARAM', 'lang');

/**
 * Handles internationalization tasks.
 * 
 * @author Ingo Hofmann
 */
class I18n {
	
	private static $_instance;
	private $_currentLanguage;
	private $_supportedLanguages;
	
	/**
	 * @param string supported languages as comma separated string
	 * @return the only instance during current request.
	 */
	public static function getInstance($supportedLanguages) {
		if(self::$_instance == NULL) {
			self::$_instance = new I18n($supportedLanguages);
		}
		return self::$_instance;
	}
	
	/**
	 * @return string current set language. If no language is selected, return the user's browser configured language. 
	 * If this is not available, use the default language.
	 */
	public function getCurrentLanguage() {
		if ($this->_currentLanguage == null) {
			
			// from session
			if (isset($_SESSION[LANG_SESSION_PARAM])) {
				$lang = $_SESSION[LANG_SESSION_PARAM];
			// from browser
			}elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$lang = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
			} else {
				// return default language
				$lang = $this->_supportedLanguages[0];
			}
			
			if (!in_array($lang, $this->_supportedLanguages)) {
				$lang = $this->_supportedLanguages[0];
			}
			
			$this->_currentLanguage = $lang;
			
		}
		return $this->_currentLanguage;
	}
	
	/**
	 * @param string $language language ISO code to set as current language.
	 */
	public function setCurrentLanguage($language) {
		if ($language == $this->_currentLanguage) {
			return;
		}
		
		$lang = strtolower($language);
		if (!in_array($lang, $this->_supportedLanguages)) {
			$lang = $this->getCurrentLanguage();
		}
		$_SESSION[LANG_SESSION_PARAM] = $lang;
		$this->_currentLanguage = $lang;
	}	
	
	/**
	 * 
	 * @param string $messageKey message key
	 * @param string $paramaters placeholder values.
	 * @return string message with specified key or ???key??? if not found.
	 */
	public function getMessage($messageKey, $paramaters = null) {
		global $msg;
		if (!$this->hasMessage($messageKey)) {
			return '???' . $messageKey .'???';
		}
		
		$message = stripslashes($msg[$messageKey]);
		if ($paramaters != null) {
			$message = sprintf($message, $paramaters);
		}
		return $message;
	}
	
	/**
	 * 
	 * @param string $messageKey message key
	 * @return boolean TRUE if message with specified key exists in the current laguage.
	 */
	public function hasMessage($messageKey) {
		global $msg;
		return isset($msg[$messageKey]);
	}	
	
	/**
	 * 
	 * @param string $pageId page id
	 * @return string label of navigation item.
	 */
	public function getNavigationLabel($pageId) {
		return $this->getMessage($pageId . PAGE_NAV_LABEL_SUFFIX);
	}
	
	/**
	 * @return array array of supported languages.
	 */
	public function getSupportedLanguages() {
		return $this->_supportedLanguages;
	}

	// hide constructor
	private function __construct($supportedLanguages) {
		$this->_supportedLanguages = array_map('trim', explode(',', $supportedLanguages));
	}	

}
?>