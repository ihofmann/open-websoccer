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
 * Escapes for HTML output. Uses <code>htmlspecialchars</code> (UTF-8).
 *
 * @param string $message message string to escape.
 * @return string escaped input string, ready for secure HTML output.
 */
function escapeOutput($message) {
	return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
}

/**
 * Sends strict security headers for AdminCenter pages.
 *
 * The Content-Security-Policy allows only same-origin scripts, styles,
 * images, fonts and connections, and explicitly forbids inline scripts,
 * inline styles, inline event handlers, plugins, framing and form
 * submissions to other origins. It must be called before any HTML output,
 * like header('Content-type: ...').
 */
function sendAdminSecurityHeaders() {
	header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
	header("X-Content-Type-Options: nosniff");
	header("X-Frame-Options: DENY");
	header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Returns the per-session CSRF token used by AdminCenter forms.
 *
 * @return string
 */
function getAdminCsrfToken() {
	if (!isset($_SESSION['admin_csrf_token'])) {
		$_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['admin_csrf_token'];
}

/**
 * Rejects forged state-changing AdminCenter requests.
 */
function validateAdminCsrfToken() {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		return;
	}

	$submittedToken = isset($_POST['admin_csrf_token']) ? $_POST['admin_csrf_token'] : '';
	$sessionToken = isset($_SESSION['admin_csrf_token']) ? $_SESSION['admin_csrf_token'] : '';
	if (!is_string($submittedToken) || !$sessionToken || !$submittedToken || !hash_equals($sessionToken, $submittedToken)) {
		http_response_code(403);
		die('Invalid CSRF token.');
	}
}

/**
 * Adds a CSRF field to each rendered AdminCenter POST form.
 *
 * Login and password-reset pages do not use the AdminCenter bootstrap and
 * therefore remain intentionally exempt.
 *
 * @param string $output
 * @return string
 */
function injectAdminCsrfFields($output) {
	$csrfField = '<input type="hidden" name="admin_csrf_token" value="' .
		escapeOutput(getAdminCsrfToken()) . '">';
	return preg_replace_callback(
		'/<form\b(?=[^>]*\bmethod\s*=\s*["\']?post\b)[^>]*>/i',
		function ($match) use ($csrfField) {
			return $match[0] . $csrfField;
		},
		$output
	);
}

/**
 * Creates code for displaying an alert with severity Warning.
 * 
 * @param string $title message title.
 * @param string $message message details.
 * @return string HTML code displaying an alert.
 */
function createWarningMessage($title, $message) {
  return createMessage('warning', $title, $message);
}

/**
 * Creates code for displaying an alert with severity Info.
 *
 * @param string $title message title.
 * @param string $message message details.
 * @return string HTML code displaying an alert.
 */
function createInfoMessage($title, $message) {
  return createMessage('info', $title, $message);
}

/**
 * Creates code for displaying an alert with severity Error.
 *
 * @param string $title message title.
 * @param string $message message details.
 * @return string HTML code displaying an alert.
 */
function createErrorMessage($title, $message) {
  return createMessage('error', $title, $message);
}

/**
 * Creates code for displaying an alert with severity Success.
 *
 * @param string $title message title.
 * @param string $message message details.
 * @return string HTML code displaying an alert.
 */
function createSuccessMessage($title, $message) {
  return createMessage('success', $title, $message);
}

/**
 * Creates code for displaying an alert with specified severity.
 *
 * @param string $severity info|warning|error|success
 * @param string $title message title.
 * @param string $message message details.
 * @return string HTML code displaying an alert.
 */
function createMessage($severity, $title, $message) {
  $cssSeverity = ($severity === 'error') ? 'danger' : $severity;
  $html = '<div class=\'alert alert-'. $cssSeverity . ' alert-dismissible fade show\' role=\'alert\'>';
  $html .= '<strong>'. $title .'</strong> ';
  $html .= $message;
  $html .= '<button type=\'button\' class=\'btn-close\' data-bs-dismiss=\'alert\' aria-label=\'Close\'></button>';
  $html .= '</div>';
  return $html;
}

/**
 * Writes a log statement into the entity log file.
 * 
 * @param WebSoccer $websoccer application context.
 * @param string $type edit|delete
 * @param string $username name of admin who executed an action.
 * @param string $entity name of affacted entity.
 * @param string $entityValue string value which identifies the entity item.
 */
function logAdminAction(WebSoccer $websoccer, $type, $username, $entity, $entityValue) {
	$userIp = getenv('REMOTE_ADDR');
	$message = $websoccer->getFormattedDatetime($websoccer->getNowAsTimestamp()) . ';' . $username . ';' . $userIp . ';' . $type . ';' . $entity . ';' . $entityValue;
	$file = BASE_FOLDER . '/generated/entitylog.php';
	
	$fw = new FileWriter($file, FALSE);
	$fw->writeLine($message);
	$fw->close();
}

?>
