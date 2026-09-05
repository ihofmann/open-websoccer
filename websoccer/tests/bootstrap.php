<?php
/**
 * PHPUnit bootstrap file for OpenWebSoccer-Sim.
 *
 * It wires up the application class autoloader and the constants that the
 * production front controller (admin/config/global.inc.php) normally defines,
 * but WITHOUT connecting to a database, starting the DB session handler or
 * loading the generated configuration cache. This keeps the unit tests fully
 * isolated from any real database connection.
 */

error_reporting(E_ALL);

// The application root (the "websoccer" folder itself).
define('BASE_FOLDER', dirname(__DIR__));

// Mirrors admin/config/global.inc.php: DEBUG is referenced by TemplateEngine
// (and ViewHandler). Defining it here lets unit tests exercise code paths
// that render e-mail templates (e.g. EmailHelper) without hitting an
// "undefined constant" error.
if (!defined('DEBUG')) {
	define('DEBUG', FALSE);
}

// Composer autoloader (provides PHPUnit and Twig).
require_once BASE_FOLDER . '/vendor/autoload.php';

// Application constants (mirrors admin/config/global.inc.php, minus DB wiring).
define('FOLDER_MODULES', BASE_FOLDER . '/modules');
define('MODULE_CONFIG_FILENAME', 'module.xml');
define('GLOBAL_CONFIG_FILE', BASE_FOLDER . '/generated/config.inc.php');
define('CONFIGCACHE_FILE_FRONTEND', BASE_FOLDER . '/cache/wsconfigfront.inc.php');
define('CONFIGCACHE_FILE_ADMIN', BASE_FOLDER . '/cache/wsconfigadmin.inc.php');
define('CONFIGCACHE_MESSAGES', BASE_FOLDER . '/cache/messages_%s.inc.php');
define('CONFIGCACHE_ADMINMESSAGES', BASE_FOLDER . '/cache/adminmessages_%s.inc.php');
define('CONFIGCACHE_ENTITYMESSAGES', BASE_FOLDER . '/cache/entitymessages_%s.inc.php');
define('CONFIGCACHE_SETTINGS', BASE_FOLDER . '/cache/settingsconfig.inc.php');
define('CONFIGCACHE_EVENTS', BASE_FOLDER . '/cache/eventsconfig.inc.php');
define('UPLOAD_FOLDER', BASE_FOLDER . '/uploads/');
define('TEMPLATES_FOLDER', BASE_FOLDER . '/templates');
define('PROFPIC_UPLOADFOLDER', UPLOAD_FOLDER . 'users');

define('PARAM_ACTION', 'action');
define('PARAM_PAGE', 'page');
define('PARAM_BLOCK', 'block');
define('PARAM_PAGENUMBER', 'pageno');
define('MSG_KEY_ERROR_PAGENOTFOUND', 'error_page_not_found');
define('ASSETS_VERSION', 'av123');

// Message type constants (mirrors FrontMessage.class.php).
define('MESSAGE_TYPE_INFO', 'info');
define('MESSAGE_TYPE_WARNING', 'warning');
define('MESSAGE_TYPE_SUCCESS', 'success');
define('MESSAGE_TYPE_ERROR', 'error');

// User / role constants (mirrors User.class.php).
define('ROLE_GUEST', 'guest');
define('ROLE_USER', 'user');
define('USER_STATUS_ENABLED', 1);
define('USER_STATUS_UNCONFIRMED', 2);

// Simulation constants (mirrors ISimulationStrategy.class.php). These are
// defined at file scope in the interface file, so we load it here to make the
// position/strength constants available to every simulation test without
// relying on autoloader ordering.
require_once BASE_FOLDER . '/classes/ISimulationStrategy.class.php';

// Youth match constants (mirrors YouthMatchSimulationExecutor.class.php).
require_once BASE_FOLDER . '/classes/YouthMatchSimulationExecutor.class.php';

/**
 * On-demand class loader mirroring admin/config/global.inc.php.
 *
 * It routes classes to their subfolder based on the naming conventions used
 * throughout the application (e.g. "*Service" -> services/).
 */
function classes_autoloader($class) {
	$subfolder = '';

	if (substr($class, -9) === 'Converter') {
		$subfolder = 'converters/';
	} else if (substr($class, -4) === 'Skin') {
		$subfolder = 'skins/';
	} else if (substr($class, -5) === 'Model') {
		$subfolder = 'models/';
	} else if (substr($class, -9) === 'Validator') {
		$subfolder = 'validators/';
	} else if (substr($class, -10) === 'Controller') {
		$subfolder = 'actions/';
	} else if (substr($class, -7) === 'Service') {
		$subfolder = 'services/';
	} else if (substr($class, -3) === 'Job') {
		$subfolder = 'jobs/';
	} else if (substr($class, -11) === 'LoginMethod') {
		$subfolder = 'loginmethods/';
	} else if (substr($class, -5) === 'Event') {
		$subfolder = 'events/';
	} else if (substr($class, -6) === 'Plugin') {
		$subfolder = 'plugins/';
	}

	@include(BASE_FOLDER . '/classes/' . $subfolder . $class . '.class.php');
}
spl_autoload_register('classes_autoloader');

// Register the test helper autoloader (PSR-4: OpenWebSoccer\Tests\).
spl_autoload_register(function ($class) {
	$prefix = 'OpenWebSoccer\\Tests\\';
	$base = __DIR__ . '/';
	if (strncmp($class, $prefix, strlen($prefix)) === 0) {
		$relative = substr($class, strlen($prefix));
		$file = $base . str_replace('\\', '/', $relative) . '.php';
		if (is_file($file)) {
			require $file;
		}
	}
});

// A session is required by several classes (User, SecurityUtil, I18n, ...).
// We use a plain filesystem session, never the DB session handler.
if (session_status() !== PHP_SESSION_ACTIVE) {
	@session_start();
}
