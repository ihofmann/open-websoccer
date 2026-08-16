<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for StadiumEnvironmentModel.
 */
final class StadiumEnvironmentModelTest extends TestCaseBase {
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
		$model = new StadiumEnvironmentModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new StadiumEnvironmentModel($this->dbMock(), $this->mockI18n(['feature_requires_team' => 'need team']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsEmptyBuildings(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(5);
		$ws->method('getUser')->willReturn($user);
		$model = new StadiumEnvironmentModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['existingBuildings']);
		$this->assertSame([], $params['availableBuildings']);
	}
}
