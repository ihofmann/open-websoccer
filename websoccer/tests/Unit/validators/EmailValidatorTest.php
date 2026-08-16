<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for EmailValidator.
 */
final class EmailValidatorTest extends TestCaseBase {
	public function testIsValidReturnsEmailForValidAddress(): void {
		$i18n = $this->mockI18n(['validation_error_email' => 'Invalid email']);
		$ws = $this->mockWebsoccer();
		$v = new EmailValidator($i18n, $ws, 'user@example.com');
		$this->assertNotFalse($v->isValid());
	}

	public function testIsValidReturnsFalseForInvalidAddress(): void {
		$i18n = $this->mockI18n(['validation_error_email' => 'Invalid email']);
		$ws = $this->mockWebsoccer();
		$v = new EmailValidator($i18n, $ws, 'not-an-email');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidReturnsFalseForEmptyString(): void {
		$i18n = $this->mockI18n(['validation_error_email' => 'Invalid email']);
		$ws = $this->mockWebsoccer();
		$v = new EmailValidator($i18n, $ws, '');
		$this->assertFalse($v->isValid());
	}

	public function testIsValidReturnsFalseForMissingDomain(): void {
		$i18n = $this->mockI18n(['validation_error_email' => 'Invalid email']);
		$ws = $this->mockWebsoccer();
		$v = new EmailValidator($i18n, $ws, 'user@');
		$this->assertFalse($v->isValid());
	}

	public function testGetMessageReturnsI18nMessage(): void {
		$i18n = $this->mockI18n(['validation_error_email' => 'Please enter a valid e-mail address.']);
		$ws = $this->mockWebsoccer();
		$v = new EmailValidator($i18n, $ws, 'bad');
		$this->assertSame('Please enter a valid e-mail address.', $v->getMessage());
	}
}
