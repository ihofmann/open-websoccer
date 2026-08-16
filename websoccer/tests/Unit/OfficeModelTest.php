<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for OfficeModel.
 */
final class OfficeModelTest extends TestCaseBase {
	private function dbMock(): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new OfficeModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyArrayForGuest(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new OfficeModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}

	public function testGetTemplateParametersReturnsEmptyArrayForManagerWithClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'randomevents_interval_days' => 0]);
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(5);
		$ws->method('getUser')->willReturn($user);
		$model = new OfficeModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}
}
