<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LeagueTableModel.
 */
final class LeagueTableModelTest extends TestCaseBase {
	private function dbMock(array $selectMap = [], array $cachedMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($selectMap) {
			foreach ($selectMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $this->dbResult($rows);
				}
			}
			return $this->dbResult([]);
		});
		$db->method('queryCachedSelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($cachedMap) {
			foreach ($cachedMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $rows;
				}
			}
			return [];
		});
		return $db;
	}

	private function websoccerWithUser(\User $user, array $config = [], array $request = []): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback(function ($name) use ($request) {
			return $request[$name] ?? null;
		});
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsFalseWhenNoLeagueAndNoClub(): void {
		$user = $this->makeUser(['id' => null]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LeagueTableModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenLeagueIdProvided(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws'], ['id' => 5]);
		$model = new LeagueTableModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenLeaguePreselectedFromClub(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$db = $this->dbMock(['_verein' => [['liga_id' => 7]]]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LeagueTableModel($db, $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsStructureForCurrentStanding(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws'], ['id' => 5]);
		$model = new LeagueTableModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(5, $params['leagueId']);
		$this->assertSame([], $params['teams']);
		$this->assertSame([], $params['markers']);
		$this->assertSame([], $params['seasons']);
	}
}
