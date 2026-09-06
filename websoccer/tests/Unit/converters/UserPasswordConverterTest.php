<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for UserPasswordConverter.
 */
final class UserPasswordConverterTest extends TestCaseBase {
	public function testToHtmlReturnsValueUnchanged(): void {
		$c = new UserPasswordConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('secret', $c->toHtml('secret'));
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new UserPasswordConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('secret', $c->toText('secret'));
	}

	public function testToDbValueHashesPasswordWithoutPostId(): void {
		$_POST = [];
		$c = new UserPasswordConverter($this->mockI18n(), $this->mockWebsoccer());
		$hash = $c->toDbValue('mypassword');
		$this->assertTrue(SecurityUtil::verifyPassword('mypassword', '', $hash));
	}

	public function testToDbValueHashesPasswordWithSaltWhenUpdating(): void {
		$_POST = ['id' => '3'];
		$salt = 'xy12';
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['passwort' => 'oldhash', 'passwort_salt' => $salt]])
		);
		\DbConnection::setInstanceForTesting($db);
		$c = new UserPasswordConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$hash = $c->toDbValue('newpass');
		$this->assertTrue(SecurityUtil::verifyPassword('newpass', $salt, $hash));
	}

	public function testToDbValueKeepsExistingPasswordWhenValueIsEmpty(): void {
		$_POST = ['id' => '3'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['passwort' => 'oldhash', 'passwort_salt' => 'xy12']])
		);
		\DbConnection::setInstanceForTesting($db);
		$c = new UserPasswordConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$this->assertSame('oldhash', $c->toDbValue(''));
	}
}
