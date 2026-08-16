<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SaveProfileController.
 */
final class SaveProfileControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testSavesProfileFieldsAndReturnsProfile(): void {
		$i18n = $this->mockI18n(['saved_message_title' => 'Saved']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 7, 'email' => 'u@e.com']));

		$updated = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new SaveProfileController($i18n, $ws, $db);
		$this->assertSame('profile', $controller->executeAction([
			'newpassword' => null, 'newemail' => null, 'birthday' => null,
			'realname' => 'John', 'place' => 'Town', 'country' => 'Land',
			'occupation' => 'Dev', 'interests' => 'Code', 'favorite_club' => 'FC',
			'homepage' => 'http://x', 'c_hideinonlinelist' => '1',
		]));
		$this->assertSame('ws_user', $updated[1]);
		$this->assertSame(7, $updated[3]);
		$this->assertSame('John', $updated[0]['name']);
		$this->assertSame('1', $updated[0]['c_hideinonlinelist']);
		// No password columns when newpassword is null.
		$this->assertArrayNotHasKey('passwort', $updated[0]);
	}

	public function testSetsPasswordColumnsWhenNewPasswordProvided(): void {
		$i18n = $this->mockI18n(['saved_message_title' => 'Saved']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 7, 'email' => 'u@e.com']));

		$updated = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new SaveProfileController($i18n, $ws, $db);
		$this->assertSame('profile', $controller->executeAction([
			'newpassword' => 'secret', 'newemail' => null, 'birthday' => null,
			'realname' => '', 'place' => '', 'country' => '', 'occupation' => '',
			'interests' => '', 'favorite_club' => '', 'homepage' => '', 'c_hideinonlinelist' => '0',
		]));
		$this->assertArrayHasKey('passwort_salt', $updated[0]);
		$this->assertArrayHasKey('passwort', $updated[0]);
		$this->assertNotSame('', $updated[0]['passwort']);
	}

	public function testParsesBirthdayAccordingToDateFormat(): void {
		$i18n = $this->mockI18n(['saved_message_title' => 'Saved']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'date_format' => 'Y-m-d']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 7, 'email' => 'u@e.com']));

		$updated = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new SaveProfileController($i18n, $ws, $db);
		$controller->executeAction([
			'newpassword' => null, 'newemail' => null, 'birthday' => '1990-05-12',
			'realname' => '', 'place' => '', 'country' => '', 'occupation' => '',
			'interests' => '', 'favorite_club' => '', 'homepage' => '', 'c_hideinonlinelist' => '0',
		]);
		$this->assertSame('1990-05-12', $updated[0]['geburtstag']);
	}
}
