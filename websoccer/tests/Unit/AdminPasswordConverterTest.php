<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for AdminPasswordConverter.
 */
final class AdminPasswordConverterTest extends TestCaseBase {
	public function testToHtmlReturnsValueUnchanged(): void {
		$c = new AdminPasswordConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('secret', $c->toHtml('secret'));
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new AdminPasswordConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('secret', $c->toText('secret'));
	}

	public function testToDbValueHashesPasswordWithoutPostId(): void {
		$_POST = [];
		$c = new AdminPasswordConverter($this->mockI18n(), $this->mockWebsoccer());
		$expected = SecurityUtil::hashPassword('mypassword', '');
		$this->assertSame($expected, $c->toDbValue('mypassword'));
	}

	public function testToDbValueHashesPasswordWithSaltWhenUpdating(): void {
		$_POST = ['id' => '7'];
		$salt = 'abcd';
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['passwort' => 'oldhash', 'passwort_salt' => $salt]])
		);
		\DbConnection::setInstanceForTesting($db);
		$c = new AdminPasswordConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$expected = SecurityUtil::hashPassword('newpass', $salt);
		$this->assertSame($expected, $c->toDbValue('newpass'));
	}

	public function testToDbValueKeepsExistingPasswordWhenValueIsEmpty(): void {
		$_POST = ['id' => '7'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['passwort' => 'oldhash', 'passwort_salt' => 'abcd']])
		);
		\DbConnection::setInstanceForTesting($db);
		$c = new AdminPasswordConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$this->assertSame('oldhash', $c->toDbValue(''));
	}
}
