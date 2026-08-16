<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NextMatchModel.
 */
final class NextMatchModelTest extends TestCaseBase {
	private function dbMock(array $cached = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new NextMatchModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyArrayWhenNoNextMatch(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NextMatchModel($this->dbMock([]), $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}

	public function testGetTemplateParametersUsesNationalTeamWhenRequested(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			// getNationalTeamManagedByCurrentUser queries a plain "id" column string.
			if (is_string($columns) && $columns === 'id') {
				return [['id' => 9]];
			}
			return [];
		});
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			return $this->dbResult([]);
		});

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'nationalteams_enabled' => TRUE]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$ws->method('getRequestParameter')->willReturnCallback(function($name) {
			return ($name === 'nationalteam') ? 1 : null;
		});
		$model = new NextMatchModel($db, $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}
}
