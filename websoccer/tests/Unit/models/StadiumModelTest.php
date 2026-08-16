<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for StadiumModel.
 */
final class StadiumModelTest extends TestCaseBase {
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
		$model = new StadiumModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsNullStadiumWhenUserHasNoClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new StadiumModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertNull($params['stadium']);
		$this->assertNull($params['construction']);
		$this->assertSame([], $params['upgradeCosts']);
	}
}
