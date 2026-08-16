<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FormationTemplatesModel.
 */
final class FormationTemplatesModelTest extends TestCaseBase {
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

	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FormationTemplatesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyTemplatesWhenNoData(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FormationTemplatesModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('templates', $params);
		$this->assertSame([], $params['templates']);
	}

	public function testGetTemplateParametersReturnsTemplatesFromDb(): void {
		$rows = [
			['id' => 1, 'date' => 100, 'templatename' => '4-3-3'],
			['id' => 2, 'date' => 200, 'templatename' => '4-4-2'],
		];
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FormationTemplatesModel($this->dbMock(['_aufstellung' => $rows]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['templates']);
		$this->assertSame($rows, $params['templates']);
	}
}
