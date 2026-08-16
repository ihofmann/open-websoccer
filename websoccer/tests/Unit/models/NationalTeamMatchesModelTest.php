<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NationalTeamMatchesModel.
 */
final class NationalTeamMatchesModelTest extends TestCaseBase {
	private function dbMock(array $cached = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrueWhenEnabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$model = new NationalTeamMatchesModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenDisabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => FALSE, 'db_prefix' => 'ws']);
		$model = new NationalTeamMatchesModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoNationalTeam(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalTeamMatchesModel($this->dbMock([]), $this->mockI18n(['nationalteams_user_requires_team' => 'no team']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsEmptyArrayWhenTeamManaged(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->dbMock([['id' => 5]]);
		$model = new NationalTeamMatchesModel($db, $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}
}
