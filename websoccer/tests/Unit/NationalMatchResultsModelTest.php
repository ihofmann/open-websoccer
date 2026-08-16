<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NationalMatchResultsModel.
 */
final class NationalMatchResultsModelTest extends TestCaseBase {
	private function dbMock(array $cached = [], int $hits = 0): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($hits) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $hits]]);
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrueWhenNationalTeamsEnabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$model = new NationalMatchResultsModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenNationalTeamsDisabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => FALSE, 'db_prefix' => 'ws']);
		$model = new NationalMatchResultsModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoNationalTeam(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalMatchResultsModel($this->dbMock([], 0), $this->mockI18n(['nationalteams_user_requires_team' => 'no team']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsPaginatorAndEmptyMatchesWhenNoSimulatedMatches(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->dbMock([['id' => 5]], 0);
		$model = new NationalMatchResultsModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('paginator', $params);
		$this->assertArrayHasKey('matches', $params);
		$this->assertSame([], $params['matches']);
		$this->assertInstanceOf(Paginator::class, $params['paginator']);
	}
}
