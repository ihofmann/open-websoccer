<?php
require_once(dirname(__FILE__).'/abstractLoggerHandler.php');

/**
 * A basic implementation of logging mechanism intended for debugging
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-08-12 10:10:46 +0200 (Mon, 12 Aug 2013) $
 * @version SOFORTLib 2.0.1  $Id: fileLogger.php 262 2013-08-12 08:10:46Z dehn $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 */
class FileLogger extends AbstractLoggerHandler {
	
	/**
	 * File Handler
	 *
	 * @var resource
	 */
	public $fp = null;
	
	/**
	 * Maximum size of a log file in Bytes
	 * @var integer
	 */
	public $maxFilesize = 1048576;
	
	
	/**
	 * Path to Logfile
	 * @var string
	 */
	protected $_logfilePath = false;
	
	/**
	 * Path to Errorlogfile
	 * @var string
	 */
	protected $_errorLogfilePath = false;
	
	/**
	 * Path to Warningslogfile
	 * @var string
	 */
	protected $_warningsLogfilePath = false;
	
	
	/**
	 * Constructor
	 * Setting the LogfilePathes
	 * @param string $path
	 */
	public function __construct($path = '') {
		$this->_logfilePath = ($path != '') ? $path : dirname(__FILE__).'/logs/log.txt';
		$this->_errorLogfilePath = dirname(__FILE__).'/logs/error_log.txt';
		$this->_warningsLogfilePath = dirname(__FILE__).'/logs/warning_log.txt';
	}
	
	
	/**
	 * Set the path of the logfile
	 * @param string $path
	 * @return void
	 */
	public function setLogfilePath($path) {
		$this->_logfilePath = $path;
	}
	
	
	/**
	 * Setting a log entry
	 * @param string_type $message
	 * @param string $log
	 * @return boolean
	 */
	public function log($message, $log = 'log') {
		return $this->_log($message, $log);
	}
	
	
	/**
	 * Logs $msg to a file which path is being set by it's unified resource locator
	 * @param string $message
	 * @param string $log (default log)
	 * @return boolean
	 */
	protected function _log($message, $log = 'log') {
		switch ($log) {
			case 'error':
				$file = $this->_errorLogfilePath;
				break;
			case 'warning':
				$file = $this->_warningsLogfilePath;
				break;
			default:
			case 'log':
				$file = $this->_logfilePath;
		}
		
		if(!is_file($file)) {
			$this->fp = fopen($file, 'w');
			fclose($this->fp);
		}
		
		if (is_writable($file)) {
			if ($log == 'log' && $this->_logRotate()) {
				$this->fp = fopen($file, 'w');
				fclose($this->fp);
			}
			
			$this->fp = fopen($file, 'a');
			fwrite($this->fp, '['.date('Y-m-d H:i:s').'] '.$message."\n");
			fclose($this->fp);
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Copy the content of the logfile to a backup file if file size got too large
	 * Put the old log file into a tarball for later reference
	 * @return boolean
	 */
	protected function _logRotate() {
		if (!is_writable($this->_logfilePath)) {
			return false;
		}
		
		$date = date('Y-m-d_h-i-s', time());
		
		if (file_exists($this->_logfilePath)) {
			if ($this->fp != null && filesize($this->_logfilePath) != false && filesize($this->_logfilePath) >= $this->maxFilesize) {
				$oldUri = $this->_logfilePath;
				// file ending
				$ending = $ext = pathinfo($oldUri, PATHINFO_EXTENSION);
				$newUri = dirname($oldUri).'/log_'.$date.'.'.$ending;
				rename($oldUri, $newUri);
				
				if (file_exists($oldUri)) {
					unlink($oldUri);
				}
				
				return true;
			}
		}
		
		return false;
	}
}
?>