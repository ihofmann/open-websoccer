<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SaveUsernameController.
 */
final class SaveUsernameControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenUserNameAlreadySet(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'illegal_usernames' => 'admin,root']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1, 'username' => 'existing']));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('user name is already set.');

		$controller = new SaveUsernameController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'newbie']);
	}

	public function testThrowsForIllegalUserName(): void {
		$i18n = $this->mockI18n(['registration_illegal_username' => 'illegal']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'illegal_usernames' => 'admin,root']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1, 'username' => '']));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal');

		$controller = new SaveUsernameController($i18n, $ws, $db);
		// 'root' is the 2nd entry -> array_search() returns 1 (truthy).
		$controller->executeAction(['nick' => 'root']);
	}

	public function testThrowsWhenUserNameExists(): void {
		$i18n = $this->mockI18n(['registration_user_exists' => 'exists']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'illegal_usernames' => 'admin,root']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1, 'username' => '']));
		$db = $this->makeDb([], ['ws_user' => [['hits' => 1]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('exists');

		$controller = new SaveUsernameController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'taken']);
	}

	public function testSavesUserNameAndReturnsOffice(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'illegal_usernames' => 'admin,root']);
		$user = $this->makeUser(['id' => 1, 'username' => '']);
		$ws->method('getUser')->willReturn($user);
		$db = $this->makeDb([], ['ws_user' => [['hits' => 0]]]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $table, $where, $params) use (&$updated) {
			$updated = [$columns, $table, $where, $params];
		});

		$controller = new SaveUsernameController($i18n, $ws, $db);
		$this->assertSame('office', $controller->executeAction(['nick' => 'newbie']));
		$this->assertSame(['nick' => 'newbie'], $updated[0]);
		$this->assertSame('newbie', $user->username);
	}
}
