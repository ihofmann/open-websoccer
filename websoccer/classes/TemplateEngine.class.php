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
define('TEMPLATE_SUBDIR_DEFAULT', 'default');
define('I18N_GLOBAL_NAME', 'i18n');
define('ENVIRONMENT_GLOBAL_NAME', 'env');
define('SKIN_GLOBAL_NAME', 'skin');
define('VIEWHANDLER_GLOBAL_NAME', 'viewHandler');
define('CACHE_FOLDER', BASE_FOLDER . '/cache/templates');

/**
 * Enables skin dependent HTML templating.
 * 
 * The underlying engine is <a href='http://twig.sensiolabs.org'>Twig</a>.
 * 
 * @author Ingo Hofmann
 */
class TemplateEngine {

	private $_environment;
	private $_skin;
	
	/**
	 * Initializes the underlying template engine.
	 */
	function __construct(WebSoccer $env, I18n $i18n, ViewHandler $viewHandler = null) {
		
		$this->_skin = $env->getSkin();
		
		$this->_initTwig();
		$this->_environment->addGlobal(I18N_GLOBAL_NAME, $i18n);
		$this->_environment->addGlobal(ENVIRONMENT_GLOBAL_NAME, $env);
		$this->_environment->addGlobal(SKIN_GLOBAL_NAME, $this->_skin);
		$this->_environment->addGlobal(VIEWHANDLER_GLOBAL_NAME, $viewHandler);
	}
	
	/**
	 * Loads the specified template.
	 * 
	 * @param string $templateName template name (NOT template file name, i.e. no file extension!).
	 * @return Twig_TemplateInterface template instance.
	 */
	public function loadTemplate($templateName) {
		return $this->_environment->loadTemplate($this->_skin->getTemplate($templateName));
	}
	
	/**
	 * deletes all cached templates.
	 */
	public function clearCache() {
		if (file_exists(CACHE_FOLDER)) {
			$this->_environment->clearCacheFiles();
		}
	}
	
	/**
	 * Provides the internal Twig environment in order to register extensions, etc.
	 * 
	 * @return Twig_Environment Twig environment instance.
	 * @since 5.0.0
	 */
	public function getEnvironment() {
		return $this->_environment;
	}
	
	private function _initTwig() {
		require_once(BASE_FOLDER . '/lib/Twig/Autoloader.php');
		Twig_Autoloader::register();
		
		// file loader
		$loader = new Twig_Loader_Filesystem(TEMPLATES_FOLDER . '/' . TEMPLATE_SUBDIR_DEFAULT);
		
		$skinSubDir = $this->_skin->getTemplatesSubDirectory();
		if (strlen($skinSubDir) && $skinSubDir != TEMPLATE_SUBDIR_DEFAULT) {
			$loader->prependPath(TEMPLATES_FOLDER .'/'. $skinSubDir);
		}
		
		// environment config
		$twigConfig = array(
				'cache' => CACHE_FOLDER,
		);
		if (DEBUG) {
			$twigConfig['auto_reload'] = TRUE;
			$twigConfig['strict_variables'] = TRUE;
		}
		
		// init
		$this->_environment = new Twig_Environment($loader, $twigConfig);
	}
	
	private function _addSettingsSupport() {
		$function = new Twig_SimpleFunction(CONFIG_FUNCTION_NAME, function ($key) {
			global $i18n;
			return $i18n->getMessage($key);
		});
		$this->_environment->addFunction($function);
	}
	
}
?>