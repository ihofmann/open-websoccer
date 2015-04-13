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
 * Base class of all events which can be used at plug-ins.
 */
abstract class AbstractEvent {
	
	/**
	 * @var WebSoccer application context.
	 */
	public $websoccer;
	
	/**
	 * @var DbConnection database connection.
	 */
	public $db;
	
	/**
	 * @var I18n messages context.
	 */
	public $i18n;
	
	/**
	 * Assigns values for application context, DB connection and messages context.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db database connection.
	 * @param I18n $i18n messages context.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n) {
		$this->websoccer = $websoccer;
		$this->db = $db;
		$this->i18n = $i18n;
	}
	
}

?>
