<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchesModel.
 */
final class YouthMatchesModelTest extends TestCaseBase {
	private function dbMock(int $count = 0, array $matches = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($count, $matches) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $count]]);
			}
			return $this->dbResult($matches);
		});
		return $db;
	}

	private function ws(array $config, \User $user): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsTrueWhenYouthEnabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$model = new YouthMatchesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(1, $model->renderView());
	}

	public function testRenderViewReturnsFalseWhenYouthDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['youth_enabled' => 0, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$model = new YouthMatchesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyMatchesWhenNone(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$model = new YouthMatchesModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('matches', $params);
		$this->assertSame([], $params['matches']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}

	public function testGetTemplateParametersReturnsMatchesFromDb(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$matches = [['match_id' => 1, 'home_team' => 'A', 'guest_team' => 'B',
			'home_user_picture' => '', 'home_user_email' => '',
			'guest_user_picture' => '', 'guest_user_email' => '']];
		$model = new YouthMatchesModel($this->dbMock(1, $matches), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['matches']);
	}
}
