<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SponsorModel.
 */
final class SponsorModelTest extends TestCaseBase {
	private function dbMock(): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) {
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 10]);
		$model = new SponsorModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 10]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new SponsorModel($this->dbMock(), $this->mockI18n(['feature_requires_team' => 'need team']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsNoSponsorAndNoOffersWhenMatchdayTooEarly(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 10]);
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(5);
		$ws->method('getUser')->willReturn($user);
		$model = new SponsorModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertFalse($params['sponsor']);
		$this->assertSame([], $params['sponsors']);
		$this->assertSame(0, $params['teamMatchday']);
	}
}
