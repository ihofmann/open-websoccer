<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for UserActivationController.
 */
final class UserActivationControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testActivatesUserAndReturnsNull(): void {
		$i18n = $this->mockI18n([
			'activate-user_message_title' => 'Activated',
			'activate-user_message_content' => 'Welcome',
		]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDb([], [
			'ws_user' => [['id' => 42]],
		]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $table, $where, $params) use (&$updated) {
			$updated = [$columns, $table, $where, $params];
		});

		$controller = new UserActivationController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['key' => 'abc', 'userid' => 42]));

		$this->assertSame(['status' => 1], $updated[0]);
		$this->assertSame('ws_user', $updated[1]);
		$this->assertSame(42, $updated[3]);
	}

	public function testThrowsWhenUserNotFound(): void {
		$i18n = $this->mockI18n(['activate-user_user-not-found' => 'not found']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		// No matching user row -> empty result.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not found');

		$controller = new UserActivationController($i18n, $ws, $db);
		$controller->executeAction(['key' => 'wrong', 'userid' => 99]);
	}
}
