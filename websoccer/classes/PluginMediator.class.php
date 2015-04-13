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
 * Mediator between plugins (event listeners) and application core.
 * Identifies existing event listeners and dispatches events to them.
 * 
 * Event listeners must be configured at any module.xml like following:
 * 
 * <code>
 * &lt;eventlistener event="{class name of event}" class="{class name of listener}" method="{listener function name}" /&gt;
 * </code>
 * 
 * @author Ingo Hofmann
 */
class PluginMediator {
	private static $_eventlistenerConfigs;
	
	/**
	 * Notifies listeners about the specified event.
	 * 
	 * @param AbstractEvent $event event instance holding data for listeners.
	 * @throws Exception if the configured listener function does not exist or if this function throws an Exception.
	 */
	public static function dispatchEvent(AbstractEvent $event) {
		if (self::$_eventlistenerConfigs == null) {
			include(CONFIGCACHE_EVENTS);
			if (isset($eventlistener)) {
				self::$_eventlistenerConfigs = $eventlistener;
			} else {
				self::$_eventlistenerConfigs = array();
			}
		}
		
		// any event listener configured?
		if (!count(self::$_eventlistenerConfigs)) {
			return;
		}
		
		// get available configurations for this event.
		$eventName = get_class($event);
		if (!isset(self::$_eventlistenerConfigs[$eventName])) {
			return;
		}
		
		// call listeners
		$eventListeners = self::$_eventlistenerConfigs[$eventName];
		foreach ($eventListeners as $listenerConfigStr) {
			$listenerConfig = json_decode($listenerConfigStr, TRUE);
			
			if (method_exists($listenerConfig['class'], $listenerConfig['method'])) {
				call_user_func($listenerConfig['class'] . '::' . $listenerConfig['method'], $event);
			} else {
				throw new Exception('Configured event listener must have function: ' . $listenerConfig['class'] . '::' . $listenerConfig['method']);
			}
			
		}
	}
	
}
?>