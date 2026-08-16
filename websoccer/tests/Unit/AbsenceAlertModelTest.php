<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for AbsenceAlertModel.
 */
final class AbsenceAlertModelTest extends TestCaseBase {
	/**
	 * Builds a WebSoccer mock whose getUser() returns the supplied user.
	 */
	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsFalseWhenUserHasNoAbsence(): void {
		$db = $this->mockDb();
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceAlertModel($db, $i18n, $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenAbsenceRecordExists(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['user_id' => 1, 'deputy_id' => 0, 'from_date' => 100, 'to_date' => 200],
		]));
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceAlertModel($db, $i18n, $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsAbsenceRecord(): void {
		$row = ['user_id' => 1, 'deputy_id' => 5, 'from_date' => 100, 'to_date' => 200];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceAlertModel($db, $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('absence', $params);
		$this->assertSame($row, $params['absence']);
	}

	public function testGetTemplateParametersReturnsNullAbsenceForGuest(): void {
		$db = $this->mockDb();
		$user = $this->makeUser(['id' => null]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();

		$model = new AbsenceAlertModel($db, $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('absence', $params);
		// No absence row -> fetch_array returns false.
		$this->assertFalse($params['absence']);
	}
}
