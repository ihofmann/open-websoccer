<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for RegisterFormController.
 */
final class RegisterFormControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'allow_userregistration' => TRUE,
			'illegal_usernames' => 'admin,root',
			'max_number_of_users' => 0,
			'register_use_captcha' => FALSE,
		];
	}

	public function testThrowsWhenRegistrationDisabled(): void {
		$i18n = $this->mockI18n(['registration_disabled' => 'disabled']);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['allow_userregistration' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('disabled');

		$controller = new RegisterFormController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'x', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);
	}

	public function testThrowsForIllegalUserName(): void {
		$i18n = $this->mockI18n(['registration_illegal_username' => 'illegal']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal');

		$controller = new RegisterFormController($i18n, $ws, $db);
		// 'root' is the 2nd illegal entry -> array_search() returns 1 (truthy).
		$controller->executeAction(['nick' => 'root', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);
	}

	public function testThrowsWhenEmailsDoNotMatch(): void {
		$i18n = $this->mockI18n(['registration_repeated_email_notmatching' => 'email mismatch']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('email mismatch');

		$controller = new RegisterFormController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'c@d.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);
	}

	public function testThrowsWhenPasswordsDoNotMatch(): void {
		$i18n = $this->mockI18n(['registration_repeated_password_notmatching' => 'password mismatch']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('password mismatch');

		$controller = new RegisterFormController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p1', 'pswd_repeat' => 'p2']);
	}

	public function testThrowsWhenUserAlreadyExists(): void {
		$i18n = $this->mockI18n(['registration_user_exists' => 'exists']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->makeDb([], ['ws_user' => [['hits' => 1]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('exists');

		$controller = new RegisterFormController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);
	}
}
