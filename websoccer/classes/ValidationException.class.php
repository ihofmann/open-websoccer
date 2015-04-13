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
 * Indicates form validation failures.
 * 
 * @author Ingo Hofmann
 */
class ValidationException extends Exception {
	
	private $_messages;
	
	/**
	 * @param array $messages array of messages which describe a failed validation.
	 */
    public function __construct($messages) {
    	$this->_messages = $messages;
    	parent::__construct('Validation failed');
    }
    
    /**
     * @return array Array of validation messages.
     */
    public function getMessages() {
    	return $this->_messages;
    }

}
?>