<?php

/**
 * Data service for reusable, language-specific website pages.
 */
class PageDataService {
	const TERMS_AND_CONDITIONS_TYPE = 'termsandconditions';
	const IMPRINT_TYPE = 'imprint';

	public static function getByTypeAndLanguage(WebSoccer $websoccer, DbConnection $db, $type, $language) {
		$result = $db->querySelect(
			array(
				'id' => 'id',
				'type' => 'type',
				'language' => 'language',
				'content' => 'content'
			),
			self::getTable($websoccer),
			'type = \'%s\' AND language = \'%s\' ORDER BY id ASC',
			array($type, $language),
			1
		);
		$page = $result->fetch_array();
		$result->free();

		return $page ?: null;
	}

	public static function save(WebSoccer $websoccer, DbConnection $db, $type, $language, $content) {
		$page = self::getByTypeAndLanguage($websoccer, $db, $type, $language);
		if ($page) {
			$db->queryUpdate(
				array('content' => $content),
				self::getTable($websoccer),
				'id = %d',
				$page['id']
			);
		} else {
			$db->queryInsert(
				array(
					'type' => $type,
					'language' => $language,
					'content' => $content
				),
				self::getTable($websoccer)
			);
		}
	}

	private static function getTable(WebSoccer $websoccer) {
		return $websoccer->getConfig('db_prefix') . '_pages';
	}
}

?>
