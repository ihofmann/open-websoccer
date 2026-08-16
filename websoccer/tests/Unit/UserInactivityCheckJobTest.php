<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;
use OpenWebSoccer\Tests\MockDbResult;

if (!defined('JOBS_CONFIG_FILE')) {
	define('JOBS_CONFIG_FILE', sys_get_temp_dir() . '/ows_jobs_test.xml');
}

/**
 * Unit tests for UserInactivityCheckJob.
 */
final class UserInactivityCheckJobTest extends TestCaseBase {
	use JobTestHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->writeJobConfig(0);
	}

	protected function tearDown(): void {
		@file_put_contents(JOBS_CONFIG_FILE, $this->jobXml(0));
		parent::tearDown();
	}

	/**
	 * The config needed by UsersDataService::getActiveUsersWithHighscore
	 * (which calls getUserProfilePicture with gravatar config).
	 */
	private function inactivityConfig(): array {
		return array_merge($this->jobConfig(), [
			'gravatar_enable' => 0,
			'context_root' => '',
		]);
	}

	public function testExecuteWithNoUsersDoesNotCallQueryUpdate(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->expects($this->never())->method('queryUpdate');

		$ws = $this->mockWebsoccer($this->inactivityConfig());
		$i18n = $this->mockI18n();

		$job = new UserInactivityCheckJob($ws, $db, $i18n, 'usractv', false);
		$job->execute();
	}

	public function testExecuteDelegatesToUsersDataService(): void {
		// Verify the job queries the user table by checking querySelect
		// is called with _user in the from table.
		$selectCalled = false;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use (&$selectCalled) {
			if (strpos($fromTable, '_user') !== false) {
				$selectCalled = true;
			}
			return new MockDbResult([]);
		});

		$ws = $this->mockWebsoccer($this->inactivityConfig());
		$i18n = $this->mockI18n();

		$job = new UserInactivityCheckJob($ws, $db, $i18n, 'usractv', false);
		$job->execute();

		$this->assertTrue($selectCalled);
	}

	public function testExecuteWithUsersCallsComputeUserInactivity(): void {
		// When users are returned, computeUserInactivity is called which
		// queries getUserInactivity and getUserById, then may call queryUpdate.
		$users = [
			['id' => 1, 'nick' => 'user1', 'email' => 'u1@test.local', 'picture' => '',
			 'highscore' => 100, 'registration_date' => 1000, 'team_id' => 5, 'team_name' => 'FC', 'team_picture' => ''],
		];
		// Inactivity row (login_check and transfer_check recent -> no update needed).
		$inactivityRow = ['id' => 1, 'user_id' => 1, 'login' => 0, 'login_check' => time(),
			'tactics' => 0, 'transfer' => 0, 'transfer_check' => time(), 'contractextensions' => 0];
		// User data row.
		$userRow = ['id' => 1, 'nick' => 'user1', 'email' => 'u1@test.local', 'highscore' => 100,
			'popularity' => 0, 'registration_date' => 1000, 'lastonline' => time(),
			'picture' => '', 'history' => '', 'name' => 'name', 'wohnort' => 'city'];

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable) use ($users, $inactivityRow, $userRow) {
			if (strpos($fromTable, '_user AS U') !== false) {
				return new MockDbResult($users);
			}
			if (strpos($fromTable, '_user_inactivity') !== false) {
				return new MockDbResult([$inactivityRow]);
			}
			// getUserById query
			return new MockDbResult([$userRow]);
		});

		$ws = $this->mockWebsoccer($this->inactivityConfig());
		$i18n = $this->mockI18n();

		$job = new UserInactivityCheckJob($ws, $db, $i18n, 'usractv', false);
		// Should complete without errors.
		$job->execute();
		$this->assertTrue(true);
	}
}
