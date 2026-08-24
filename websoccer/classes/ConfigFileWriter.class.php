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
 * Writes entries into the global config file (defined in global constant GLOBAL_CONFIG_FILE).
 * 
 * @author Ingo Hofmann
 */
class ConfigFileWriter {

	private static $_instance;
	private $_settings;
	
	/**
	 * Provides request's only instance of this class.
	 * 
	 * @param array $settings existing settings.
	 * @return the only instance during current request.
	 */
	public static function getInstance($settings) {
        if(self::$_instance == NULL) {
			self::$_instance = new ConfigFileWriter($settings);
		}
        return self::$_instance;
    }
    
    /**
     * Updates or adds the passed settings in the global config file.
     * 
     * @param array $newSettings array of settings to update or add.
     */
    public function saveSettings($newSettings) {
    	
    	// update or add values
    	foreach($newSettings as $settingId => $settingValue) {
    		$this->_settings[$settingId] = $settingValue;
    	}
    	
    	$this->_writeToFile();
    }
    
    private function _writeToFile() {
    	$content = "<?php" . PHP_EOL;
    	
    	foreach ($this->_settings as $id => $value) {
    		// Escape characters that have special meaning inside PHP double-quoted
    		// strings: backslash, double-quote and dollar sign.  Single quotes do
    		// NOT need escaping (and addslashes' \' would be preserved literally,
    		// corrupting values such as CSP source lists that contain 'self').
    		$escapedValue = addcslashes($value, '\\$"');
    		$content .= "\$conf[\"". $id . "\"] = \"". $escapedValue ."\";". PHP_EOL;
    	}
    	
    	$content .= "?>";
    	
    	$fw = new FileWriter(GLOBAL_CONFIG_FILE);
    	$fw->writeLine($content);
    	$fw->close();
    }
    
    // hide constructor
    private function __construct($settings) {
    	$this->_settings = $settings;
    }
	
}
?>