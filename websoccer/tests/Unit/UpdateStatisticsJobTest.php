<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;

if (!defined('JOBS_CONFIG_FILE')) {
	define('JOBS_CONFIG_FILE', sys_get_temp_dir() . '/ows_jobs_test.xml');
}

/**
 * Unit tests for UpdateStatisticsJob.
 */
final class UpdateStatisticsJobTest extends TestCaseBase {
	use JobTestHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->writeJobConfig(0);
	}

	protected function tearDown(): void {
		@file_put_contents(JOBS_CONFIG_FILE, $this->jobXml(0));
		parent::tearDown();
	}

	public function testExecuteCallsExecuteQueryTwice(): void {
		$db = $this->createMock(\DbConnection::class);
		// execute() runs two executeQuery calls: the statistics REPLACE INTO
		// and the team strength UPDATE.
		$db->expects($this->exactly(2))->method('executeQuery');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new UpdateStatisticsJob($ws, $db, $i18n, 'stats', false);
		$job->execute();
	}

	public function testExecuteStatisticsQueryContainsExpectedTableNames(): void {
		$executedQueries = [];

		$db = $this->createMock(\DbConnection::class);
		$db->method('executeQuery')->willReturnCallback(function ($query) use (&$executedQueries) {
			$executedQueries[] = $query;
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new UpdateStatisticsJob($ws, $db, $i18n, 'stats', false);
		$job->execute();

		// First query: REPLACE INTO statistics table.
		$this->assertStringContainsString('REPLACE INTO ws_team_league_statistics', $executedQueries[0]);
		$this->assertStringContainsString('ws_spiel', $executedQueries[0]);
		$this->assertStringContainsString('ws_verein', $executedQueries[0]);

		// Second query: UPDATE team strengths.
		$this->assertStringContainsString('UPDATE ws_verein', $executedQueries[1]);
		$this->assertStringContainsString('ws_spieler', $executedQueries[1]);
	}

	public function testExecuteStatisticsQueryUsesThreePointsForWin(): void {
		$executedQueries = [];

		$db = $this->createMock(\DbConnection::class);
		$db->method('executeQuery')->willReturnCallback(function ($query) use (&$executedQueries) {
			$executedQueries[] = $query;
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new UpdateStatisticsJob($ws, $db, $i18n, 'stats', false);
		$job->execute();

		// The query uses $pointsWin = 3 for win calculations.
		$this->assertStringContainsString('* 3', $executedQueries[0]);
	}

	public function testExecuteUsesDbPrefixFromConfig(): void {
		$executedQueries = [];

		$db = $this->createMock(\DbConnection::class);
		$db->method('executeQuery')->willReturnCallback(function ($query) use (&$executedQueries) {
			$executedQueries[] = $query;
		});

		$ws = $this->mockWebsoccer(array_merge($this->jobConfig(), ['db_prefix' => 'customprefix']));
		$i18n = $this->mockI18n();

		$job = new UpdateStatisticsJob($ws, $db, $i18n, 'stats', false);
		$job->execute();

		$this->assertStringContainsString('customprefix_team_league_statistics', $executedQueries[0]);
		$this->assertStringContainsString('customprefix_verein', $executedQueries[1]);
	}
}
