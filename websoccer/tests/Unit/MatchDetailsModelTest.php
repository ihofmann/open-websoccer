<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for MatchDetailsModel.
 */
final class MatchDetailsModelTest extends TestCaseBase {
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

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new MatchDetailsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenNoMatchIdProvided(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new MatchDetailsModel($this->mockDb(), $this->mockI18n(), $ws);

		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersThrowsWhenMatchNotFound(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws'], ['id' => 5]);
		$model = new MatchDetailsModel($this->mockDb(), $this->mockI18n(), $ws);

		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsStructureWhenMatchFound(): void {
		$user = $this->makeUser(['id' => 1]);
		$match = ['match_id' => 5, 'match_minutes' => 0, 'match_simulated' => 1,
			'match_home_id' => 1, 'match_guest_id' => 2];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->method('queryCachedSelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($match) {
			if (strpos($fromTable, '_spiel AS M') !== false) {
				return [$match];
			}
			return [];
		});
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'sim_allow_livechanges' => FALSE], ['id' => 5]);

		$model = new MatchDetailsModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('match', $params);
		$this->assertArrayHasKey('reportmessages', $params);
		$this->assertArrayHasKey('allowTacticChanges', $params);
		$this->assertArrayHasKey('homeStrikerMessages', $params);
		$this->assertArrayHasKey('guestStrikerMessages', $params);
		$this->assertFalse($params['allowTacticChanges']);
		$this->assertSame([], $params['reportmessages']);
	}
}
