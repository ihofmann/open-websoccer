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
		$expected = SecurityUtil::hashPassword('mypassword', '');
		$this->assertSame($expected, $c->toDbValue('mypassword'));
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
		$expected = SecurityUtil::hashPassword('newpass', $salt);
		$this->assertSame($expected, $c->toDbValue('newpass'));
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
