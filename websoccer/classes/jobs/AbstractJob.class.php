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
 * Base class of all jobs. Jobs are NOT Cron-Jobs, but simply scripts which are executed without break.
 * 
 * @author Ingo Hofmann
 */
abstract class AbstractJob {
	
	protected $_websoccer;
	protected $_db;
	protected $_i18n;
	
	private $_id;
	private $_interval;
	
	/**
	 * 
	 * @param WebSoccer $websoccer request context.
	 * @param DbConnection $db database connection-
	 * @param I18n $i18n messages context.
	 * @param string $jobId Job ID as defined at jobs.xml.
	 * @param $errorOnAlreadyRunning boolean TRUE if error shall occur on init time when an instance of this job is already running.
	 */
	function __construct(WebSoccer $websoccer, DbConnection $db, I18n $i18n, $jobId, $errorOnAlreadyRunning = TRUE) {
		$this->_websoccer = $websoccer;
		$this->_db = $db;
		$this->_i18n = $i18n;
		
		$this->_id = $jobId;
		
		$xmlConfig = $this->getXmlConfig();
		
		// check if another instance is running (consider timeout of 5 minutes)
		if ($errorOnAlreadyRunning) {
			$initTime = (int) $xmlConfig->attributes()->inittime;
			$now = $websoccer->getNowAsTimestamp();
			$timeoutTime = $now - 5 * 60;
			if ($initTime > $timeoutTime) {
				throw new Exception('Another instance of this job is already running.');
			}
			$this->replaceAttribute('inittime', $now);
		}
		
		$interval = (int) $xmlConfig->attributes()->interval;
		$this->_interval = $interval * 60;
		
		ignore_user_abort(TRUE);
		// run possibly forever
		set_time_limit(0);
		
		// enable garbage collection (in case it is disabled by default)
		gc_enable();
	}
	
	/**
	 * Destructor resets marker for checking the only instance.
	 */
	function __destruct() {
		// little hack: set the ping so that 'last execution' also works for external job executions.
		// Better solution would be a AOP implementation which creates an interceptor for execute() function,
		// but for now this should also lead to the same behavior.
		$this->_ping($this->_websoccer->getNowAsTimestamp());
		
		$this->replaceAttribute('inittime', 0);
	}
	
	/**
	 * Starts the job. Pauses the script after each iteration.
	 */
	public function start() {
		
		// reset stopping
		$this->replaceAttribute('stop', '0');
		$this->replaceAttribute('error', '');
		$this->_ping(0);
		
		do {
			
			$xmlConfig = $this->getXmlConfig();
			$stop = (int) $xmlConfig->attributes()->stop;
			if ($stop === 1) {
				$this->stop();
			}
			
			$now = $this->_websoccer->getNowAsTimestamp();
			
			// check if lastping has been set by another job. then this job became obsolete
			$lastPing = (int) $xmlConfig->attributes()->last_ping;
			if ($lastPing > 0) {
				$myOwnLastPing = $now - $this->_interval + 5; //plus tolerance
				if ($lastPing > $myOwnLastPing) {
					exit;
				}
			}
			
			$this->_ping($now);	
				
			try {
				// reconnect to db
				$this->_db->close();
				$this->_db->connect($this->_websoccer->getConfig('db_host'),
					$this->_websoccer->getConfig('db_user'),
					$this->_websoccer->getConfig('db_passwort'),
					$this->_websoccer->getConfig('db_name'));
				
				$this->execute();
				
				// force freeing memory by garbage collector
				gc_collect_cycles();
			} catch (Exception $e) {
				$this->replaceAttribute('error', $e->getMessage());
				$this->stop();
			}
				
			$this->_db->close();
			
			sleep($this->_interval);
		} while(true);
	}
	
	/**
	 * Stops the job.
	 */
	public function stop() {
		$this->replaceAttribute('stop', '1');
		
		exit;
	}
	
	private function _ping($time) {
		$this->replaceAttribute('last_ping', $time);
	}
	
	private function getXmlConfig() {
		$xml = simplexml_load_file(JOBS_CONFIG_FILE);
		$xmlConfig = $xml->xpath('//job[@id = \''. $this->_id . '\']');
		if (!$xmlConfig) {
			throw new Exception('Job config not found.');
		}
		
		return $xmlConfig[0];
	}
	
	private function replaceAttribute($name, $value) {
		
		// lock file for this transaction
		$fp = fopen(BASE_FOLDER . '/admin/config/lockfile.txt', 'r');
		flock($fp, LOCK_EX);
		
		$xml = simplexml_load_file(JOBS_CONFIG_FILE);
		if ($xml === FALSE) {
			
			$errorMessages = '';
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$errorMessages = $errorMessages . "\n" . $error;
			}
			throw new Exception('Job with ID \'' . $this->_id . '\': Could not update attribute \'' . $name . '\' with value \'' . $value . '\'. Errors: ' . $errorMessages);
		}
		$xmlConfig = $xml->xpath('//job[@id = \''. $this->_id . '\']');
		$xmlConfig[0][$name] = $value;
		$successfulWritten = $xml->asXML(JOBS_CONFIG_FILE);
		if (!$successfulWritten) {
			throw new Exception('Job with ID \'' . $this->_id . '\': Could not save updated attribute \'' . $name . '\' with value \'' . $value . '\'.');
		}
		
		// unlock
		flock($fp, LOCK_UN);
	}
	
	/**
	 * Execute this job by calling program code.
	 */
	abstract function execute();
}

?>
