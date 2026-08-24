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

	/**
	 * Builds a DbConnection mock whose querySelect results are dispatched by
	 * inspecting the $whereCondition, so the nickname check, the e-mail
	 * existence check and the send-password lookup can return different rows.
	 *
	 * @param array   $nickRows        rows for the nickname collision check.
	 * @param array   $emailRows       rows for the e-mail existence check.
	 * @param array   $sendPasswordRows rows for the send-password user lookup.
	 * @param array   $updateCalls     reference populated with queryUpdate calls.
	 */
	private function makeDbByWhere(array $nickRows, array $emailRows, array $sendPasswordRows, array &$updateCalls): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $params = null, $limit = null) use ($nickRows, $emailRows, $sendPasswordRows) {
				if (strpos($whereCondition, 'nick') !== false) {
					return $this->dbResult($nickRows);
				}
				if (strpos($whereCondition, 'status') !== false) {
					return $this->dbResult($sendPasswordRows);
				}
				return $this->dbResult($emailRows);
			}
		);
		$db->method('queryUpdate')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $params = null) use (&$updateCalls) {
				$updateCalls[] = ['columns' => $columns, 'where' => $whereCondition, 'params' => $params];
				return null;
			}
		);
		return $db;
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

	public function testThrowsWhenNicknameAlreadyExists(): void {
		$i18n = $this->mockI18n(['registration_user_exists' => 'exists']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$updateCalls = [];
		// Nickname collision (hits=1); e-mail is free (hits=0).
		$db = $this->makeDbByWhere([['hits' => 1]], [['hits' => 0]], [], $updateCalls);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('exists');

		$controller = new RegisterFormController($i18n, $ws, $db);
		$controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);
	}

	public function testShowsSuccessWhenEmailAlreadyRegisteredButNoActiveUser(): void {
		$i18n = $this->mockI18n([
			'register-success_message_title' => 'success title',
			'register-success_message_content' => 'success content',
		]);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$added = [];
		$ws->method('addFrontMessage')->willReturnCallback(function ($m) use (&$added) { $added[] = $m; });
		$updateCalls = [];
		// Nickname free; e-mail already in use; send-password lookup finds no
		// active user (e.g. the existing account is still inactive).
		$db = $this->makeDbByWhere([['hits' => 0]], [['hits' => 1]], [], $updateCalls);

		$controller = new RegisterFormController($i18n, $ws, $db);
		$result = $controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);

		$this->assertSame('register-success', $result);
		$this->assertCount(1, $added);
		$this->assertSame(MESSAGE_TYPE_SUCCESS, $added[0]->type);
		$this->assertSame('success title', $added[0]->title);
		// No password reset is triggered because there is no active user.
		$this->assertSame([], $updateCalls);
	}

	public function testShowsSuccessWhenEmailRegisteredAndPasswordAlreadyRecentlyRequested(): void {
		$i18n = $this->mockI18n([
			'register-success_message_title' => 'success title',
			'register-success_message_content' => 'success content',
		]);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$added = [];
		$ws->method('addFrontMessage')->willReturnCallback(function ($m) use (&$added) { $added[] = $m; });
		$updateCalls = [];
		// E-mail in use; active user found, but a reset was already requested
		// within the last 24h (timestamp == now), so no new reset is created.
		$db = $this->makeDbByWhere(
			[['hits' => 0]],
			[['hits' => 1]],
			[['id' => 5, 'passwort_salt' => 'salt', 'passwort_neu_angefordert' => 1000000]],
			$updateCalls
		);

		$controller = new RegisterFormController($i18n, $ws, $db);
		$result = $controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);

		$this->assertSame('register-success', $result);
		$this->assertSame(MESSAGE_TYPE_SUCCESS, $added[0]->type);
		$this->assertSame([], $updateCalls);
	}

	public function testTriggersPasswordResetWhenEmailRegisteredForActiveUser(): void {
		$i18n = $this->mockI18n([
			'register-success_message_title' => 'success title',
			'register-success_message_content' => 'success content',
			'sendpassword_email_subject' => 'pwd subject',
		]);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$added = [];
		$ws->method('addFrontMessage')->willReturnCallback(function ($m) use (&$added) { $added[] = $m; });
		// A skin is required by TemplateEngine (used by EmailHelper). Mail
		// delivery itself fails (no mail server in unit tests), which the
		// controller treats as success.
		$skin = $this->createMock(\ISkin::class);
		$skin->method('getTemplatesSubDirectory')->willReturn('default');
		$skin->method('getTemplate')->willReturnCallback(function ($name) { return $name . '.twig'; });
		$ws->method('getSkin')->willReturn($skin);
		$updateCalls = [];
		// E-mail in use; active user found, last reset long ago -> a new
		// password reset must be triggered (queryUpdate called). The
		// subsequent mail delivery failure is silently ignored.
		$db = $this->makeDbByWhere(
			[['hits' => 0]],
			[['hits' => 1]],
			[['id' => 5, 'passwort_salt' => 'salt', 'passwort_neu_angefordert' => 0]],
			$updateCalls
		);

		$controller = new RegisterFormController($i18n, $ws, $db);
		$result = $controller->executeAction(['nick' => 'newbie', 'email' => 'a@b.com', 'email_repeat' => 'a@b.com', 'pswd' => 'p', 'pswd_repeat' => 'p']);

		$this->assertSame('register-success', $result);
		$this->assertSame(MESSAGE_TYPE_SUCCESS, $added[0]->type);
		// A password reset update was issued for the existing user.
		$this->assertCount(1, $updateCalls);
		$this->assertSame('id = %d', $updateCalls[0]['where']);
		$this->assertSame(5, $updateCalls[0]['params']);
	}
}
