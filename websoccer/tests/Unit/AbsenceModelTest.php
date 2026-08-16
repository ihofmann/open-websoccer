<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for AbsenceModel.
 */
final class AbsenceModelTest extends TestCaseBase {
	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$db = $this->mockDb();
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceModel($db, $i18n, $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyDeputyNameWhenNoAbsence(): void {
		$db = $this->mockDb();
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceModel($db, $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('currentAbsence', $params);
		$this->assertArrayHasKey('deputyName', $params);
		$this->assertSame('', $params['deputyName']);
	}

	public function testGetTemplateParametersReturnsDeputyNameWhenDeputySet(): void {
		// First querySelect call: absence record with deputy_id.
		// Second querySelect call: deputy user nick.
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['user_id' => 1, 'deputy_id' => 7, 'from_date' => 100, 'to_date' => 200]]),
			$this->dbResult([['nick' => 'deputybob']])
		);
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceModel($db, $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('deputybob', $params['deputyName']);
		$this->assertSame(7, $params['currentAbsence']['deputy_id']);
	}

	public function testGetTemplateParametersReturnsEmptyDeputyNameWhenDeputyIdIsZero(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['user_id' => 1, 'deputy_id' => 0, 'from_date' => 100, 'to_date' => 200],
		]));
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceModel($db, $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('', $params['deputyName']);
	}
}
