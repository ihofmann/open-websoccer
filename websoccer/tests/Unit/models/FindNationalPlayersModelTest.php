<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FindNationalPlayersModel.
 */
final class FindNationalPlayersModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsTrueWhenNationalTeamsEnabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'nationalteams_enabled' => TRUE, 'entries_per_page' => 20]);
		$model = new FindNationalPlayersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenNationalTeamsDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'nationalteams_enabled' => FALSE, 'entries_per_page' => 20]);
		$model = new FindNationalPlayersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserManagesNoNationalTeam(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'nationalteams_enabled' => TRUE, 'entries_per_page' => 20]);
		$model = new FindNationalPlayersModel($this->dbMock(), $this->mockI18n(), $ws);

		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsStructureWhenNationalTeamManaged(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->dbMock(
			['_verein' => [['name' => 'Germany']]],
			['_verein' => [['id' => 10]]]
		);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'nationalteams_enabled' => TRUE, 'entries_per_page' => 20], ['search' => 'true']);

		$model = new FindNationalPlayersModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('Germany', $params['team_name']);
		$this->assertSame(0, $params['playersCount']);
		$this->assertSame([], $params['players']);
		$this->assertArrayHasKey('paginator', $params);
	}
}
