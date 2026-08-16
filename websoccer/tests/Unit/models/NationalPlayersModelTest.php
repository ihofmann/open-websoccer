<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NationalPlayersModel.
 */
final class NationalPlayersModelTest extends TestCaseBase {
	private function dbMock(array $cached = [], array $teamRows = [], array $playerRows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn($cached);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($teamRows, $playerRows) {
			if (is_string($fromTable) && stripos($fromTable, '_spieler') !== false) {
				return $this->dbResult($playerRows);
			}
			return $this->dbResult($teamRows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrueWhenEnabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$model = new NationalPlayersModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenDisabled(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => FALSE, 'db_prefix' => 'ws']);
		$model = new NationalPlayersModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoNationalTeam(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new NationalPlayersModel($this->dbMock([], [], []), $this->mockI18n(['nationalteams_user_requires_team' => 'no team']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsTeamNameAndEmptyPlayers(): void {
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => TRUE, 'db_prefix' => 'ws', 'players_aging' => 'birthday']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->dbMock([['id' => 5]], [['name' => 'Germany']], []);
		$model = new NationalPlayersModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('Germany', $params['team_name']);
		$this->assertSame([], $params['players']);
	}
}
