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
 * Writes into files. Supports both truncating and appending.
 * 
 * @author Ingo Hofmann
 */
class FileWriter {

	private $_filePointer;
	
	/**
	 * Opens a file for writing. If the file does not exist, it gets created.
	 * 
	 * @param string $file file name
	 * @param boolean $truncateExistingFile if TRUE and the file already exists the file gets truncated (previous content getting deleted).
	 * @throws Exception if file could not be created.
	 */
	function __construct($file, $truncateExistingFile = TRUE) {
		$this->_filePointer = @fopen($file, ($truncateExistingFile) ? 'w' : 'a');
		if ($this->_filePointer === FALSE) {
			throw new Exception('Could not create or open file '. $file .'! Verify that the file or its folder is writable.');
		}
	}
	
	/**
	 * Writes a new line into the opened file.
	 * 
	 * @param string $line string to write, without line break.
	 * @throws Exception if line could not be written.
	 */
	public function writeLine($line) {
		if (@fwrite($this->_filePointer, $line . PHP_EOL) === FALSE) {
			throw new Exception('Could not write line \''. $line . '\' into file '. $file .'!');
		}
	}
	
	/**
	 * closes file writer.
	 */
	public function close() {
		if ($this->_filePointer) {
			@fclose($this->_filePointer);
		}
	}
	
	function __destruct() {
		$this->close();
	}
	
}
?>