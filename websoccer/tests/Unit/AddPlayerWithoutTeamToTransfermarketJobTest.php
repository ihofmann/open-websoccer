<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;
use OpenWebSoccer\Tests\MockDbResult;

if (!defined('JOBS_CONFIG_FILE')) {
	define('JOBS_CONFIG_FILE', sys_get_temp_dir() . '/ows_jobs_test.xml');
}

/**
 * Unit tests for AddPlayerWithoutTeamToTransfermarketJob.
 */
final class AddPlayerWithoutTeamToTransfermarketJobTest extends TestCaseBase {
	use JobTestHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->writeJobConfig(0);
	}

	protected function tearDown(): void {
		@file_put_contents(JOBS_CONFIG_FILE, $this->jobXml(0));
		parent::tearDown();
	}

	public function testExecuteWithNoPlayersDoesNotCallQueryUpdate(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->expects($this->never())->method('queryUpdate');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AddPlayerWithoutTeamToTransfermarketJob($ws, $db, $i18n, 'addplyr', false);
		$job->execute();
	}

	public function testExecuteWithPlayersCallsQueryUpdate(): void {
		// The service queries players without team, then for each player
		// queries the team summary. With a player whose team has no manager
		// (user_id=null), the player gets a new contract (queryUpdate).
		$playerRow = ['id' => 1, 'verein_id' => 5];
		$teamRow = ['team_id' => 5, 'user_id' => null, 'team_name' => 'FC Test',
			'team_budget' => 1000, 'team_picture' => '', 'team_league_name' => 'L1', 'team_league_id' => 1];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($playerRow, $teamRow) {
			if (strpos($fromTable, '_spieler') !== false) {
				return new MockDbResult([$playerRow]);
			}
			return new MockDbResult([$teamRow]);
		});
		// getTeamSummaryById uses queryCachedSelect, not querySelect.
		$db->method('queryCachedSelect')->willReturn([$teamRow]);
		$db->expects($this->atLeastOnce())->method('queryUpdate');

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AddPlayerWithoutTeamToTransfermarketJob($ws, $db, $i18n, 'addplyr', false);
		$job->execute();
	}

	public function testExecuteDelegatesToTransfermarketDataService(): void {
		// Verify the job calls the service by checking querySelect is invoked
		// with the player table (proving the service method was called).
		$selectCalled = false;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use (&$selectCalled) {
			if (strpos($fromTable, '_spieler') !== false) {
				$selectCalled = true;
			}
			return new MockDbResult([]);
		});

		$ws = $this->mockWebsoccer($this->jobConfig());
		$i18n = $this->mockI18n();

		$job = new AddPlayerWithoutTeamToTransfermarketJob($ws, $db, $i18n, 'addplyr', false);
		$job->execute();

		$this->assertTrue($selectCalled);
	}
}
