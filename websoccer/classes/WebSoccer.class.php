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
 * Core functions and application context state of the current request.
 * 
 * @author Ingo Hofman
 */
class WebSoccer {
	
	private static $_instance;
	
	private $_skin;
	private $_pageId;
	private $_templateEngine;
	private $_frontMessages;
	private $_isAjaxRequest;
	private $_user;
	private $_contextParameters;
	
	/**
	 * @return the only instance during current request.
	 */
	public static function getInstance() {
        if(self::$_instance == NULL) {
			self::$_instance = new WebSoccer();
		}
        return self::$_instance;
    }
    
    /**
     * @return User the request's current user
     */
    public function getUser() {
    	if ($this->_user == null) {
    		$this->_user = new User();
    	}
    	
    	return $this->_user;
    }
	
	/**
	 * @return general config value.
	 * @throws Exception if configuration parameter could not be found.
	 */
	public function getConfig($name) {
		global $conf;
		if (!isset($conf[$name])) {
			throw new Exception('Missing configuration: ' . $name);
		}
		return $conf[$name];
	}
	
	/**
	 * @return action config value.
	 * @throws Exception if there is no action with specified ID.
	 */
	public function getAction($id) {
		global $action;
		if (!isset($action[$id])) {
			throw new Exception('Action not found: ' . $id);
		}
		return $action[$id];
	}
	
	/**
	 * @return currently selected skin.
	 * @throws Exception if configured class does not exist.
	 */
	public function getSkin() {
		if ($this->_skin == NULL) {
			$skinName = $this->getConfig('skin');
			if (class_exists($skinName)) {
				$this->_skin = new $skinName($this);
			} else {
				throw new Exception('Configured skin \''. $skinName . '\' does not exist. Check the system settings.');
			}
		}
		
		return $this->_skin;
	}
	
	/**
	 * @return current internal page ID
	 */
	public function getPageId() {
		return $this->_pageId;
	}
	
	/**
	 * Set the ID of page to display. Will be considered for the template selection and identifying the active menu items in the navigation bar.
	 * 
	 * @param string $pageId current internal page ID
	 */
	public function setPageId($pageId) {
		$this->_pageId = $pageId;
	}
	
	/**
	 * 
	 * @param I18n $i18n current I18n instance.
	 * @param ViewHandler $viewHandler view handler to use for templates. Can also be NULL.
	 * @return TemplateEngine current template engine to use.
	 */
	public function getTemplateEngine($i18n, ViewHandler $viewHandler = null) {
		if ($this->_templateEngine == NULL) {
			$this->_templateEngine = new TemplateEngine($this, $i18n, $viewHandler);
		}
		
		return $this->_templateEngine;
	}
	
	/**
	 * 
	 * @param string $name request parameter name
	 * @return string|NULL trimmed request parameter value or NULL if no value provided.
	 */
	public function getRequestParameter($name) {
		if (isset($_REQUEST[$name])) {
			$value = trim($_REQUEST[$name]);
			if (strlen($value)) {
				return $value;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Build a valid URL to an internal page which can be used at the frontend.
	 * This method could be used to produce 'fancy' URLs. 
	 * 
	 * @param string $pageId internal page ID or NULL for current page id.
	 * @param string $queryString query string to append to URL.
	 * @param boolean $fullUrl if TRUE, provide full URL incl. domain and protocol.
	 * @return string URL to specified internal page ID.
	 */
	public function getInternalUrl($pageId = null, $queryString = '', $fullUrl = FALSE) {
		if ($pageId == null) {
			$pageId = $this->getPageId();
		}
		
		if (strlen($queryString)) {
			$queryString = '&' . $queryString;
		}
		
		if ($fullUrl) {
			$url = $this->getConfig('homepage') . $this->getConfig('context_root');
			
			// do not provide full path to home page until required, in order to improve SEO.
			if ($pageId != 'home' || strlen($queryString)) {
				$url .= '/?page=' . $pageId . $queryString;
			}
		} else {
			$url = $this->getConfig('context_root') . '/?page=' . $pageId . $queryString;
		}
		
		return $url;
	}
	
	/**
	 * same as getInternalUrl, but appending an action call.
	 * 
	 * @param string $actionId Action ID as defined at module.xml
	 * @param string $queryString  query string to append to URL.
	 * @param string $pageId internal page ID or NULL for current page id.
	 * @param boolean $fullUrl if TRUE, provide full URL incl. domain and protocol.
	 * @return string URL to specified internal page ID including action call.
	 */
	public function getInternalActionUrl($actionId, $queryString = '', $pageId = null, $fullUrl = FALSE) {
		if ($pageId == null) {
			$pageId = $this->getRequestParameter('page');
		}
		
		if (strlen($queryString)) {
			$queryString = '&' . $queryString;
		}
		
		$url = $this->getConfig('context_root') . '/?page=' . $pageId . $queryString .'&action=' . $actionId;
		if ($fullUrl) {
			$url = $this->getConfig('homepage') . $url;
		}
		
		return $url;
	}
	
	/**
	 * 
	 * @param int $timestamp UNIX timestamp or NULL (then the current date is taken)
	 * @return string formatted date (without time).
	 */
	public function getFormattedDate($timestamp = null) {
		if ($timestamp == null) {
			$timestamp = $this->getNowAsTimestamp();
		}
		return date($this->getConfig('date_format'), $timestamp);
	}
	
	/**
	 * 
	 * @param int $timestamp UNIX timestamp or NULL (then the current time is taken)
	 * @param I18n|NULL $i18n If provided, the date will be converted into 'Today'/'Yesterday'/'Tomorrow' if applicable.
	 * @return string formatted date and time.
	 */
	public function getFormattedDatetime($timestamp, I18n $i18n = null) {
		if ($timestamp == null) {
			$timestamp = $this->getNowAsTimestamp();
		}
		
		if ($i18n != null) {
			$dateWord = StringUtil::convertTimestampToWord($timestamp, $this->getNowAsTimestamp(), $i18n);
			if (strlen($dateWord)) {
				return $dateWord . ', ' . date($this->getConfig('time_format'), $timestamp);
			}
		}
		
		return date($this->getConfig('datetime_format'), $timestamp);
	}
	
	/**
	 * @return int current timestamp, also considering a server time offset.
	 */
	public function getNowAsTimestamp() {
		return time() +  $this->getConfig('time_offset');
	}
	
	/**
	 * deletes and rebuilds general config cache files.
	 */
	public function resetConfigCache() {
		$i18n = I18n::getInstance($this->getConfig('supported_languages'));
		$cacheBuilder = new ConfigCacheFileWriter($i18n->getSupportedLanguages());
		$cacheBuilder->buildConfigCache();
	}
	
	/**
	 * @param FrontMessage $message alert message to add to the context.
	 */
	public function addFrontMessage(FrontMessage $message) {
		$this->_frontMessages[] = $message;
	}
	
	/**
	 * 
	 * @return array of FrontMessage instances which have been added to the current context.
	 */
	public function getFrontMessages() {
		if ($this->_frontMessages == null) {
			$this->_frontMessages = array();
		}
		return $this->_frontMessages;
	}
	
	/**
	 * @param boolean $isAjaxRequest flag to indicate whether current request is an AJAX request.
	 */
	public function setAjaxRequest($isAjaxRequest) {
		$this->_isAjaxRequest = $isAjaxRequest;
	}
	
	/**
	 * @return boolean TRUE if current request is an AJAX request. FALSE otherwise.
	 */
	public function isAjaxRequest() {
		return $this->_isAjaxRequest;
	}
	
	/**
	 * Provides context parameters. These are any kind of values collected in an array, which might be added during the
	 * page request lifecycle. E.g. this enables passing values from an action to a page or block model.
	 * Parameters can be added via addContextParameter().
	 * 
	 * @see WebSoccer::addContextParameter()
	 * @return array Assoc array of context parameters with key=parameter name; value=parameter value. Empty array if no parameters have been added.
	 */
	public function getContextParameters() {
		if ($this->_contextParameters == null) {
			$this->_contextParameters = array();
		}
		
		return $this->_contextParameters;
	}
	
	/**
	 * Adds a new parameter to the request context.
	 * 
	 * @see WebSoccer::getContextParameters()
	 * 
	 * @param string $name parameter name.
	 * @param mixed $value any kind of value to store during this request.
	 */
	public function addContextParameter($name, $value) {
		if ($this->_contextParameters == null) {
			$this->_contextParameters = array();
		}
		$this->_contextParameters[$name] = $value;
	}
	
	// hide constructor (Singleton)
	private function __construct() {
		$this->_isAjaxRequest = FALSE;
	}
}
?>