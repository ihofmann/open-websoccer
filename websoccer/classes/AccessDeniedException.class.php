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
 * Exception that occurs when an action or page has been requested which is not granted for the requesting user.
 */
class AccessDeniedException extends Exception {
	
	/**
	 * Creates new exception.
	 * 
	 * @param string $message Message.
	 * @param integer $code Error code.
	 */
	public function __construct($message, $code = 0) {
		parent::__construct($message, $code);
	}
	
}
?>