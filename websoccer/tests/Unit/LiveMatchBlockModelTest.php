<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LiveMatchBlockModel.
 */
final class LiveMatchBlockModelTest extends TestCaseBase {
	private function dbMock(array $cachedMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
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

	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsZeroWhenNoLiveMatch(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LiveMatchBlockModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testRenderViewReturnsNonZeroWhenLiveMatchExists(): void {
		$user = $this->makeUser(['id' => 1]);
		$match = ['match_id' => 9, 'match_type' => 'Ligaspiel', 'match_home_id' => 1, 'match_home_name' => 'A',
			'match_guest_id' => 2, 'match_guest_name' => 'B', 'match_date' => 100];
		$db = $this->dbMock(['_spiel AS M' => [$match]]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LiveMatchBlockModel($db, $this->mockI18n(), $ws);
		$this->assertGreaterThan(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsMatchKey(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LiveMatchBlockModel($this->dbMock(), $this->mockI18n(), $ws);
		// renderView() must be invoked first to populate the internal match.
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('match', $params);
		$this->assertSame([], $params['match']);
	}
}
