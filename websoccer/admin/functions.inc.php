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
	return htmlspecialchars($message, ENT_COMPAT, 'UTF-8');
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
  $html = '<div class=\'alert alert-'. $severity . '\'>';
  $html .= '<button type=\'button\' class=\'close\' data-dismiss=\'alert\'>&times;</button>';
  $html .= '<h4>'. $title .'</h4>';
  $html .= $message;
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
	$file = BASE_FOLDER . '/admin/config/entitylog.php';
	
	$fw = new FileWriter($file, FALSE);
	$fw->writeLine($message);
	$fw->close();
}

?>
