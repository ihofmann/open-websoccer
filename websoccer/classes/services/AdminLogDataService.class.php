<?php

/**
 * Data service for AdminCenter login log records.
 */
class AdminLogDataService {

	public static function create(WebSoccer $websoccer, DbConnection $db, $adminName, $ip, $createdDate = null) {
		$db->queryInsert(array(
			'admin_name' => $adminName,
			'ip' => $ip,
			'created_date' => $createdDate ?? $websoccer->getNowAsTimestamp()
		), self::getTable($websoccer));
	}

	public static function getLatest(WebSoccer $websoccer, DbConnection $db, $limit = 50) {
		$table = self::getTable($websoccer);
		$result = $db->querySelect(
			array(
				'id' => 'log_id',
				'admin_name' => 'user_name',
				'ip' => 'ip',
				'created_date' => 'created_date'
			),
			$table,
			'1 ORDER BY created_date DESC, id DESC',
			null,
			$limit
		);

		$logs = array();
		while ($log = $result->fetch_array()) {
			$logs[] = $log;
		}
		$result->free();

		return $logs;
	}

	public static function deleteOlderThan(WebSoccer $websoccer, DbConnection $db, $threshold) {
		$db->queryDelete(self::getTable($websoccer), 'created_date < %d', $threshold);
	}

	private static function getTable(WebSoccer $websoccer) {
		return $websoccer->getConfig('db_prefix') . '_adminlog';
	}
}

?>
