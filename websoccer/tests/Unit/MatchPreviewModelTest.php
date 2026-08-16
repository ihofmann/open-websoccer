<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class MatchPreviewModelTest extends TestCaseBase {
	private function model(): MatchPreviewModel {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([[
			'match_id'=>1, 'match_simulated'=>'0', 'match_minutes'=>0,
			'match_home_id'=>10, 'match_guest_id'=>20, 'match_season_id'=>0,
			'match_cup_name'=>''
		]]);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$ws = $this->mockWebsoccer(['db_prefix'=>'ws']);
		$ws->method('getRequestParameter')->willReturn(1);
		return new MatchPreviewModel($db, $this->mockI18n(), $ws);
	}
	public function testUnsimulatedMatchRenders(): void { $this->assertTrue($this->model()->renderView()); }
	public function testTemplateParametersContainMatchAndRecentMatches(): void {
		$p = $this->model()->getTemplateParameters();
		$this->assertArrayHasKey('match', $p);
		$this->assertArrayHasKey('latestMatchesHome', $p);
		$this->assertArrayHasKey('latestMatchesGuest', $p);
	}
}
