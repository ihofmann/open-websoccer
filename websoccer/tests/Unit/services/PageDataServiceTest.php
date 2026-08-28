<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for PageDataService.
 */
final class PageDataServiceTest extends TestCaseBase {
	public function testSaveUpdatesExistingPage(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 7, 'content' => 'old']]));
		$db->expects($this->once())->method('queryUpdate')
			->with(['content' => 'new'], 'ws_pages', 'id = %d', 7);
		$db->expects($this->never())->method('queryInsert');

		PageDataService::save($ws, $db, 'termsandconditions', 'en', 'new');
	}

	public function testSaveInsertsMissingLanguagePage(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->once())->method('queryInsert')
			->with(
				[
					'type' => 'termsandconditions',
					'language' => 'en',
					'content' => 'new',
				],
				'ws_pages'
			);
		$db->expects($this->never())->method('queryUpdate');

		PageDataService::save($ws, $db, 'termsandconditions', 'en', 'new');
	}
}
