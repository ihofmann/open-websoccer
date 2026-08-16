<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SendPasswordController.
 */
final class SendPasswordControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'login_allow_sendingpassword' => TRUE,
			'register_use_captcha' => FALSE,
		];
	}

	public function testThrowsWhenActionDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['login_allow_sendingpassword' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Action is disabled.');

		$controller = new SendPasswordController($i18n, $ws, $db);
		$controller->executeAction(['useremail' => 'a@b.com']);
	}

	public function testThrowsWhenEmailNotFound(): void {
		$i18n = $this->mockI18n(['forgot-password_email-not-found' => 'not found']);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		// No matching user.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not found');

		$controller = new SendPasswordController($i18n, $ws, $db);
		$controller->executeAction(['useremail' => 'ghost@b.com']);
	}

	public function testThrowsWhenPasswordAlreadyRequestedRecently(): void {
		$i18n = $this->mockI18n(['forgot-password_already-sent' => 'already sent']);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		// passwort_neu_angefordert == now -> within the 24h boundary.
		$db = $this->makeDb([], ['ws_user' => [['id' => 5, 'passwort_salt' => 'salt', 'passwort_neu_angefordert' => 1000000]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('already sent');

		$controller = new SendPasswordController($i18n, $ws, $db);
		$controller->executeAction(['useremail' => 'a@b.com']);
	}
}
