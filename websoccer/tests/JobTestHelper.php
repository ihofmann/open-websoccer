<?php
namespace OpenWebSoccer\Tests;

/**
 * Helper trait for job unit tests.
 */
trait JobTestHelper {

	/**
	 * Returns a database row matching a job definition.
	 */
	protected function jobRow(string $id, int $inittime = 0): array {
		$intervals = [
			'testjob' => 5,
			'stadium' => 30,
			'addplyr' => 5,
			'extransf' => 5,
			'sim' => 1,
			'usractv' => 20,
			'stats' => 30,
		];

		return [
			'id' => $id,
			'name' => $id,
			'name_de' => $id,
			'class' => 'TestConcreteJob',
			'interval' => $intervals[$id] ?? 5,
			'last_ping' => 0,
			'stop' => 1,
			'error' => '',
			'inittime' => $inittime,
		];
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
