<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchesDataService.
 */
final class YouthMatchesDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'gravatar_enable' => 0,
			'context_root' => '/soccer',
		]);
	}

	public function testGetYouthMatchinfoByIdReturnsMatch(): void {
		$i18n = $this->mockI18n([]);
		$row = ['id' => 1, 'home_team_name' => 'Home', 'guest_team_name' => 'Guest', 'home_goals' => 2, 'guest_goals' => 1];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$this->assertSame($row, YouthMatchesDataService::getYouthMatchinfoById($this->ws, $db, $i18n, 1));
	}

	public function testGetYouthMatchinfoByIdThrowsWhenNotFound(): void {
		$i18n = $this->mockI18n(['error_page_not_found' => 'Page not found.']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Page not found.');
		YouthMatchesDataService::getYouthMatchinfoById($this->ws, $db, $i18n, 999);
	}

	public function testCountMatchesOfTeamOnSameDayReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 2]]));
		$ts = strtotime('2024-03-15 12:00:00');
		$this->assertSame(2, YouthMatchesDataService::countMatchesOfTeamOnSameDay($this->ws, $db, 7, $ts));
	}

	public function testCountMatchesOfTeamOnSameDayReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthMatchesDataService::countMatchesOfTeamOnSameDay($this->ws, $db, 7, time()));
	}

	public function testCountMatchesOfTeamReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 5]]));
		$this->assertSame(5, YouthMatchesDataService::countMatchesOfTeam($this->ws, $db, 7));
	}

	public function testCountMatchesOfTeamReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, YouthMatchesDataService::countMatchesOfTeam($this->ws, $db, 7));
	}

	public function testGetMatchesOfTeamReturnsListWithUserPictures(): void {
		$rows = [
			['match_id' => 1, 'home_team' => 'H', 'home_team_picture' => '', 'home_id' => 1, 'home_user_id' => 10, 'home_user_nick' => 'u1', 'home_user_email' => '', 'home_user_picture' => 'p.jpg', 'guest_team' => 'G', 'guest_team_picture' => '', 'guest_id' => 2, 'guest_user_id' => 20, 'guest_user_nick' => 'u2', 'guest_user_email' => '', 'guest_user_picture' => '', 'home_goals' => 1, 'guest_goals' => 1, 'simulated' => 1, 'date' => 100],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$matches = YouthMatchesDataService::getMatchesOfTeam($this->ws, $db, 7, 0, 10);
		$this->assertCount(1, $matches);
		$this->assertSame('/soccer/uploads/users/p.jpg', $matches[0]['home_user_picture']);
		$this->assertNull($matches[0]['guest_user_picture']);
	}

	public function testGetMatchesOfTeamReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], YouthMatchesDataService::getMatchesOfTeam($this->ws, $db, 7, 0, 10));
	}

	public function testCreateMatchReportItemInsertsWithJsonData(): void {
		$captured = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryInsert')->willReturnCallback(function ($cols, $table) use (&$captured) {
			$captured = ['cols' => $cols, 'table' => $table];
		});
		YouthMatchesDataService::createMatchReportItem($this->ws, $db, 5, 12, 'goal', ['player' => 'John'], true);
		$this->assertSame(5, $captured['cols']['match_id']);
		$this->assertSame(12, $captured['cols']['minute']);
		$this->assertSame('goal', $captured['cols']['message_key']);
		$this->assertSame('{"player":"John"}', $captured['cols']['message_data']);
		$this->assertSame('1', $captured['cols']['home_on_ball']);
		$this->assertStringContainsString('_youthmatch_reportitem', $captured['table']);
	}

	public function testCreateMatchReportItemWithNullDataAndGuestBall(): void {
		$captured = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryInsert')->willReturnCallback(function ($cols) use (&$captured) {
			$captured = $cols;
		});
		YouthMatchesDataService::createMatchReportItem($this->ws, $db, 5, 10, 'kickoff', null, false);
		$this->assertSame('', $captured['message_data']);
		$this->assertSame('0', $captured['home_on_ball']);
	}

	public function testGetMatchReportItemsReplacesPlaceholders(): void {
		$i18n = $this->mockI18n([
			'goal' => 'Goal by {player}',
			'kickoff' => 'Kick off',
		]);
		$rows = [
			['minute' => 5, 'message_key' => 'kickoff', 'message_data' => '', 'home_on_ball' => '1'],
			['minute' => 12, 'message_key' => 'goal', 'message_data' => '{"player":"John"}', 'home_on_ball' => '1'],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$items = YouthMatchesDataService::getMatchReportItems($this->ws, $db, $i18n, 5);
		$this->assertCount(2, $items);
		$this->assertSame('Kick off', $items[0]['message']);
		$this->assertSame('Goal by John', $items[1]['message']);
		$this->assertSame(12, $items[1]['minute']);
	}

	public function testGetMatchReportItemsEscapesPlaceholderValues(): void {
		$i18n = $this->mockI18n(['goal' => 'Goal by {player}']);
		$rows = [
			['minute' => 10, 'message_key' => 'goal', 'message_data' => '{"player":"<script>"}', 'home_on_ball' => '0'],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$items = YouthMatchesDataService::getMatchReportItems($this->ws, $db, $i18n, 5);
		$this->assertSame('Goal by &lt;script&gt;', $items[0]['message']);
	}

	public function testGetMatchReportItemsReturnsEmptyWhenNone(): void {
		$i18n = $this->mockI18n([]);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], YouthMatchesDataService::getMatchReportItems($this->ws, $db, $i18n, 5));
	}
}
