<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NationalNextMatchModel.
 */
final class NationalNextMatchModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsFalseWhenDisabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => FALSE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalNextMatchModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenUserHasNoNationalTeam(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalNextMatchModel($this->dbMock([], 0), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenNoNextMatches(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalNextMatchModel($this->dbMock([['id' => 5]], 0), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenNextMatchesExist(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalNextMatchModel($this->dbMock([['id' => 5]], 3), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}
}
