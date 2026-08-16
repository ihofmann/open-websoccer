<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for RecaptchaService.
 */
final class RecaptchaServiceTest extends TestCaseBase {
	public function testRenderReturnsDivWithSiteKey(): void {
		$html = RecaptchaService::render('abc123');
		$this->assertStringContainsString('g-recaptcha', $html);
		$this->assertStringContainsString('data-sitekey="abc123"', $html);
	}

	public function testRenderReturnsApiScriptTag(): void {
		$html = RecaptchaService::render('abc123');
		$this->assertStringContainsString('https://www.google.com/recaptcha/api.js', $html);
		$this->assertStringContainsString('async', $html);
		$this->assertStringContainsString('defer', $html);
	}

	public function testRenderEscampsSpecialCharactersInSiteKey(): void {
		$html = RecaptchaService::render('<script>');
		$this->assertStringContainsString('&lt;script&gt;', $html);
		$this->assertStringNotContainsString('<script>', $html);
	}

	public function testVerifyReturnsFalseForEmptyResponse(): void {
		// Empty response is short-circuited by the library without a network call.
		$this->assertFalse(RecaptchaService::verify('secret', ''));
	}

	public function testVerifyReturnsFalseForEmptyResponseWithRemoteIp(): void {
		$this->assertFalse(RecaptchaService::verify('secret', '', '127.0.0.1'));
	}
}
