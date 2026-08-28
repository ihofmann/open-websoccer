<?php

/**
 * Data service for job definitions and runtime state.
 */
class JobDataService {

	public static function getJob(WebSoccer $websoccer, DbConnection $db, $jobId) {
		$result = $db->querySelect(
			'*',
			self::getTable($websoccer),
			'id = \'%s\'',
			$jobId,
			1
		);
		$job = $result->fetch_array();
		$result->free();

		return $job ?: null;
	}

	public static function getJobs(WebSoccer $websoccer, DbConnection $db) {
		$result = $db->querySelect(
			'*',
			self::getTable($websoccer),
			'1 ORDER BY id ASC'
		);

		$jobs = array();
		while ($job = $result->fetch_array()) {
			$jobs[] = $job;
		}
		$result->free();

		return $jobs;
	}

	public static function updateJob(WebSoccer $websoccer, DbConnection $db, $jobId, array $columns) {
		$db->queryUpdate(
			$columns,
			self::getTable($websoccer),
			'id = \'%s\'',
			$jobId
		);
	}

	private static function getTable(WebSoccer $websoccer) {
		return $websoccer->getConfig('db_prefix') . '_jobs';
	}
}

?>
