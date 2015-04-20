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
 * Creates converters. See IConverter interface.
 * 
 * @author Ingo Hofmann
 */
class ConverterFactory {
	
	private static $_createdConverters;

	/**
	 * Instanciates and provides converter with specified class name, if it exists.
	 * 
	 * @param WebSoccer $website Application context.
	 * @param I18n $i18n I18n context.
	 * @param string $converter converter class name.
	 * @throws Exception if converter cannot be found.
	 * @return IConverter converter instance.
	 */
	public static function getConverter($website, $i18n, $converter) {
		
		if (isset(self::$_createdConverters[$converter])) {
			return self::$_createdConverters[$converter];
		}
		
		if (class_exists($converter)) {
			$converterInstance = new $converter($i18n, $website);
			self::$_createdConverters[$converter] = $converterInstance;
			return $converterInstance;
		}
		
		throw new Exception("Converter not found: " . $converter);
	}
	
}
?>