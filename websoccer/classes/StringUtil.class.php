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
 * Util class for string handling.
 * 
 * @author Ingo Hofmann
 */
class StringUtil {
	
	/**
	 * checks if specified message starts with needle.
	 * 
	 * @param string $message message string to check.
	 * @param string $needle needle string.
	 * @return boolean TRUE if message starts with needle.
	 */
	public static function startsWith($message, $needle) {
		return !strncmp($message, $needle, strlen($needle));
	}
	
	/**
	 * checks if specified message ends with needle.
	 * 
	 * @param string $message message string to check.
	 * @param string $needle needle string.
	 * @return boolean TRUE if message ends with needle.
	 */
	public static function endsWith($message, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}
	
		return (substr($message, -$length) === $needle);
	}	
	
	/**
	 * Converts a date into a word, if it makes sense.
	 * 
	 * @param int $timestamp UNIX timestamp.
	 * @param int $nowAsTimestamp Now as UNIX timestamp, considering server timezone.
	 * @param I18n $i18n Messages context.
	 * @return string Translated Today|Tomorrow|Yesterday or empty string if date is out of range.
	 */
	public static function convertTimestampToWord($timestamp, $nowAsTimestamp, I18n $i18n) {
		
		if ($timestamp >= strtotime('tomorrow', $nowAsTimestamp) + 24 * 3600) {
			return '';
		}
		
		if ($timestamp >= strtotime('tomorrow', $nowAsTimestamp)) {
			return $i18n->getMessage('date_tomorrow');
		} else if ($timestamp >= strtotime('today', $nowAsTimestamp)) {
			return $i18n->getMessage('date_today');
		} else if ($timestamp >= strtotime('yesterday', $nowAsTimestamp)) {
			return $i18n->getMessage('date_yesterday');
		}
		
		return '';
	}
}

?>
