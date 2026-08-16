<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for HallOfFameModel.
 */
final class HallOfFameModelTest extends TestCaseBase {
	private function dbMock(array $selectMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($selectMap) {
			foreach ($selectMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $this->dbResult($rows);
				}
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new HallOfFameModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyLeaguesAndCupsWhenNoData(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new HallOfFameModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('leagues', $params);
		$this->assertArrayHasKey('cups', $params);
		$this->assertSame([], $params['leagues']);
		$this->assertSame([], $params['cups']);
	}

	public function testGetTemplateParametersGroupsLeaguesByLeagueName(): void {
		$seasons = [
			['league_name' => 'Premier', 'league_country' => 'EN', 'season_name' => '2023', 'team_id' => 1, 'team_name' => 'A', 'team_picture' => ''],
			['league_name' => 'Premier', 'league_country' => 'EN', 'season_name' => '2022', 'team_id' => 2, 'team_name' => 'B', 'team_picture' => ''],
		];
		$cups = [
			['cup_name' => 'FA Cup', 'team_id' => 1, 'team_name' => 'A', 'team_picture' => ''],
		];
		$db = $this->dbMock(['_saison AS S' => $seasons, '_cup AS CUP' => $cups]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new HallOfFameModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['leagues']['Premier']);
		$this->assertCount(1, $params['cups']);
	}
}
