<?php
use OpenWebSoccer\Tests\TestCaseBase;

// Force the class file to load so its top-level define('COOKIE_PREFIX', 'ws')
// is available before any test method references the constant.
class_exists('CookieHelper');

/**
 * Unit tests for CookieHelper.
 *
 * setcookie() in CLI mode does not actually set $_COOKIE; we manipulate
 * $_COOKIE directly to simulate cookie state.
 */
final class CookieHelperTest extends TestCaseBase {
	public function testGetCookieValueReturnsNullWhenCookieNotSet(): void {
		$_COOKIE = [];
		$this->assertNull(CookieHelper::getCookieValue('test'));
	}

	public function testGetCookieValueReturnsValueWhenCookieSet(): void {
		$_COOKIE = [COOKIE_PREFIX . 'test' => 'abc123'];
		$this->assertSame('abc123', CookieHelper::getCookieValue('test'));
	}

	public function testGetCookieValueReturnsNullForDifferentName(): void {
		$_COOKIE = [COOKIE_PREFIX . 'other' => 'val'];
		$this->assertNull(CookieHelper::getCookieValue('test'));
	}

	public function testCreateCookieWithLifetimeDoesNotThrow(): void {
		$_COOKIE = [];
		// setcookie() in CLI returns bool; just verify no exception is thrown.
		CookieHelper::createCookie('session', 'token-value', 30);
		$this->assertTrue(true);
	}

	public function testCreateCookieWithNullLifetimeDoesNotThrow(): void {
		$_COOKIE = [];
		CookieHelper::createCookie('session', 'token-value', null);
		$this->assertTrue(true);
	}

	public function testDestroyCookieDoesNothingWhenCookieNotSet(): void {
		$_COOKIE = [];
		CookieHelper::destroyCookie('nonexistent');
		$this->assertTrue(true);
	}

	public function testDestroyCookieDoesNotThrowWhenCookieExists(): void {
		$_COOKIE = [COOKIE_PREFIX . 'test' => 'val'];
		CookieHelper::destroyCookie('test');
		$this->assertTrue(true);
	}

	public function testCookiePrefixIsWs(): void {
		$this->assertSame('ws', COOKIE_PREFIX);
	}
}
