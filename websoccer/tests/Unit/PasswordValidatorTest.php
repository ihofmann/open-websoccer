<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for PasswordValidator.
 */
final class PasswordValidatorTest extends TestCaseBase {
	public function testIsValidAcceptsPasswordWithLetterAndNumber(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'abc1234');
		$this->assertTrue($v->isValid());
	}

	public function testIsValidRejectsPasswordWithoutNumber(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'abcdef');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidRejectsPasswordWithoutLetter(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, '123456');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidRejectsBlacklistedTest123(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'test123');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidRejectsBlacklistedAbc123(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'abc123');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidRejectsBlacklistedPassw0rd(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'passw0rd');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidRejectsBlacklistedPassw0rt(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'passw0rt');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidRejectsBlacklistedCaseInsensitively(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Bad password']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'TEST123');
		$this->assertFalse($v->isValid());
	}

	public function testGetMessageReturnsI18nMessage(): void {
		$i18n = $this->mockI18n(['validation_error_password' => 'Password is too weak.']);
		$ws = $this->mockWebsoccer();
		$v = new PasswordValidator($i18n, $ws, 'bad');
		$this->assertSame('Password is too weak.', $v->getMessage());
	}
}
