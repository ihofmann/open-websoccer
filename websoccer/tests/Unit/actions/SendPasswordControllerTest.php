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

	public function testShowsSuccessWhenEmailNotFound(): void {
		$i18n = $this->mockI18n([
			'forgot-password_message_title' => 'sent title',
			'forgot-password_message_content' => 'sent content',
		]);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$added = [];
		$ws->method('addFrontMessage')->willReturnCallback(function ($m) use (&$added) { $added[] = $m; });
		// No matching user.
		$db = $this->mockDb();

		$controller = new SendPasswordController($i18n, $ws, $db);
		$result = $controller->executeAction(['useremail' => 'ghost@b.com']);

		$this->assertSame('login', $result);
		$this->assertCount(1, $added);
		$this->assertSame(MESSAGE_TYPE_SUCCESS, $added[0]->type);
		$this->assertSame('sent title', $added[0]->title);
	}

	public function testShowsSuccessWhenPasswordAlreadyRequestedRecently(): void {
		$i18n = $this->mockI18n([
			'forgot-password_message_title' => 'sent title',
			'forgot-password_message_content' => 'sent content',
		]);
		$ws = $this->mockWebsoccerAt(1000000, $this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$added = [];
		$ws->method('addFrontMessage')->willReturnCallback(function ($m) use (&$added) { $added[] = $m; });
		// passwort_neu_angefordert == now -> within the 24h boundary.
		$db = $this->makeDb([], ['ws_user' => [['id' => 5, 'passwort_salt' => 'salt', 'passwort_neu_angefordert' => 1000000]]]);

		$controller = new SendPasswordController($i18n, $ws, $db);
		$result = $controller->executeAction(['useremail' => 'a@b.com']);

		$this->assertSame('login', $result);
		$this->assertCount(1, $added);
		$this->assertSame(MESSAGE_TYPE_SUCCESS, $added[0]->type);
	}
}
