<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FinancesSummaryModel.
 */
final class FinancesSummaryModelTest extends TestCaseBase {
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
		$db->method('queryCachedSelect')->willReturn([]);
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

	public function testRenderViewReturnsFalseWhenUserHasNoClub(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FinancesSummaryModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenUserHasClub(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FinancesSummaryModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyMajorPositionsWhenNoData(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FinancesSummaryModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('majorPositions', $params);
		$this->assertSame([], $params['majorPositions']);
	}

	public function testGetTemplateParametersReturnsMajorPositionsFromDb(): void {
		$rows = [
			['subject' => 'salary', 'balance' => -1000, 'avgAmount' => -100],
		];
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new FinancesSummaryModel($this->dbMock(['_konto' => $rows]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['majorPositions']);
		$this->assertSame($rows, $params['majorPositions']);
	}
}
