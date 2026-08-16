<?php
namespace OpenWebSoccer\Tests;

/**
 * Helper trait for job unit tests.
 *
 * AbstractJob's constructor reads from JOBS_CONFIG_FILE (an XML file) and
 * its destructor writes back to it. This trait sets up a temporary XML
 * file containing every known job ID so that constructors and destructors
 * work correctly even when multiple job test files run in the same process.
 */
trait JobTestHelper {

	/**
	 * All job IDs that appear in the real jobs.xml, plus a "testjob" ID
	 * used by AbstractJobTest. Including all of them in the temp file
	 * prevents destructor failures when tests from different files run
	 * in the same process.
	 */
	protected function jobXml(?int $inittime = null): string {
		$it = $inittime ?? 0;
		$jobs = [
			['id' => 'testjob', 'interval' => '5', 'inittime' => (string)$it],
			['id' => 'stadium', 'interval' => '30', 'inittime' => (string)$it],
			['id' => 'addplyr', 'interval' => '5', 'inittime' => (string)$it],
			['id' => 'extransf', 'interval' => '5', 'inittime' => (string)$it],
			['id' => 'sim', 'interval' => '1', 'inittime' => (string)$it],
			['id' => 'usractv', 'interval' => '20', 'inittime' => (string)$it],
			['id' => 'stats', 'interval' => '30', 'inittime' => (string)$it],
		];
		$xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n<jobs>\n";
		foreach ($jobs as $job) {
			$xml .= '    <job id="' . $job['id'] . '" interval="' . $job['interval'] . '"'
				. ' last_ping="" stop="1" error="" inittime="' . $job['inittime'] . '"/>' . "\n";
		}
		$xml .= "</jobs>\n";
		return $xml;
	}

	/**
	 * Writes a fresh jobs XML file to the temp path and returns the path.
	 */
	protected function writeJobConfig(?int $inittime = null): string {
		$path = JOBS_CONFIG_FILE;
		file_put_contents($path, $this->jobXml($inittime));
		return $path;
	}

	/**
	 * Standard config map for job tests.
	 */
	protected function jobConfig(): array {
		return [
			'db_prefix' => 'ws',
			'stadium_construction_delay' => 7,
			'transfermarket_duration_days' => 3,
			'supported_languages' => 'en',
		];
	}
}
