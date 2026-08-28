<?php

/**
 * Data service for AdminCenter entity change log records.
 */
class EntityLogDataService {

	public static function create(WebSoccer $websoccer, DbConnection $db, $type, $username, $entity, $entityValue, $createdDate = null, $ip = null) {
		$db->queryInsert(array(
			'created_date' => $createdDate ?? $websoccer->getNowAsTimestamp(),
			'username' => $username,
			'ip' => $ip ?? getenv('REMOTE_ADDR'),
			'type' => $type,
			'entity' => $entity,
			'entity_value' => $entityValue
		), self::getTable($websoccer));
	}

	public static function getLatest(WebSoccer $websoccer, DbConnection $db, $limit = 50) {
		$result = $db->querySelect(
			array(
				'id' => 'log_id',
				'created_date' => 'created_date',
				'username' => 'username',
				'ip' => 'ip',
				'type' => 'type',
				'entity' => 'entity',
				'entity_value' => 'entity_value'
			),
			self::getTable($websoccer),
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

	private static function getTable(WebSoccer $websoccer) {
		return $websoccer->getConfig('db_prefix') . '_entitylog';
	}
}

?>
