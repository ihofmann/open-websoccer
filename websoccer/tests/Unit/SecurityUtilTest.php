<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SecurityUtil.
 */
final class SecurityUtilTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		// Ensure HTTP_USER_AGENT is available for session-hijacking checks.
		$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit-Test-Agent/1.0';
	}

	public function testHashPasswordIsDeterministic(): void {
		$a = SecurityUtil::hashPassword('secret', 'salt');
		$b = SecurityUtil::hashPassword('secret', 'salt');
		$this->assertSame($a, $b);
	}

	public function testHashPasswordDiffersForDifferentPasswords(): void {
		$a = SecurityUtil::hashPassword('secret', 'salt');
		$b = SecurityUtil::hashPassword('other', 'salt');
		$this->assertNotSame($a, $b);
	}

	public function testHashPasswordDiffersForDifferentSalts(): void {
		$a = SecurityUtil::hashPassword('secret', 'salt1');
		$b = SecurityUtil::hashPassword('secret', 'salt2');
		$this->assertNotSame($a, $b);
	}

	public function testHashPasswordMatchesExpectedSha256Value(): void {
		$expected = hash('sha256', 'salt' . hash('sha256', 'password'));
		$this->assertSame($expected, SecurityUtil::hashPassword('password', 'salt'));
	}

	public function testGeneratePasswordReturnsStringOfLength8(): void {
		$this->assertSame(8, strlen(SecurityUtil::generatePassword()));
	}

	public function testGeneratePasswordUsesCharsetCharactersOnly(): void {
		$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789%!=?';
		$allowed = str_split($charset);
		// Generate several passwords to increase confidence.
		for ($i = 0; $i < 20; $i++) {
			$pw = SecurityUtil::generatePassword();
			foreach (str_split($pw) as $char) {
				$this->assertContains($char, $allowed, 'Character not in charset: ' . $char);
			}
		}
	}

	public function testGeneratePasswordSaltReturnsStringOfLength4(): void {
		$this->assertSame(4, strlen(SecurityUtil::generatePasswordSalt()));
	}

	public function testGenerateSessionTokenIsDeterministicForSameInputs(): void {
		$_SESSION['HTTP_USER_AGENT'] = 'agent-hash';
		$a = SecurityUtil::generateSessionToken(42, 'salt');
		$b = SecurityUtil::generateSessionToken(42, 'salt');
		$this->assertSame($a, $b);
	}

	public function testGenerateSessionTokenDiffersForDifferentUserIds(): void {
		$_SESSION['HTTP_USER_AGENT'] = 'agent-hash';
		$a = SecurityUtil::generateSessionToken(1, 'salt');
		$b = SecurityUtil::generateSessionToken(2, 'salt');
		$this->assertNotSame($a, $b);
	}

	public function testGenerateSessionTokenDiffersForDifferentSalts(): void {
		$_SESSION['HTTP_USER_AGENT'] = 'agent-hash';
		$a = SecurityUtil::generateSessionToken(1, 'salt1');
		$b = SecurityUtil::generateSessionToken(1, 'salt2');
		$this->assertNotSame($a, $b);
	}

	public function testGenerateSessionTokenUsesNaWhenNoUserAgentInSession(): void {
		unset($_SESSION['HTTP_USER_AGENT']);
		$expected = md5('salt' . 'n.a.' . 1);
		$this->assertSame($expected, SecurityUtil::generateSessionToken(1, 'salt'));
	}

	public function testIsAdminLoggedInReturnsFalseWhenNoValidSessionSet(): void {
		// First call sets the user agent hash but valid is not set.
		$result = SecurityUtil::isAdminLoggedIn();
		$this->assertFalse($result);
	}

	public function testIsAdminLoggedInReturnsTrueWhenValidSessionSet(): void {
		$_SESSION['HTTP_USER_AGENT'] = md5('PHPUnit-Test-Agent/1.0');
		$_SESSION['valid'] = true;
		$this->assertTrue(SecurityUtil::isAdminLoggedIn());
	}

	public function testIsAdminLoggedInReturnsFalseWhenValidIsFalse(): void {
		$_SESSION['HTTP_USER_AGENT'] = md5('PHPUnit-Test-Agent/1.0');
		$_SESSION['valid'] = false;
		$this->assertFalse(SecurityUtil::isAdminLoggedIn());
	}

	public function testIsAdminLoggedInLogsOutOnUserAgentMismatch(): void {
		$_SESSION['HTTP_USER_AGENT'] = md5('different-agent');
		$_SESSION['valid'] = true;
		$_SESSION['some_data'] = 'data';

		$result = SecurityUtil::isAdminLoggedIn();
		$this->assertFalse($result);

		// logoutAdmin clears the session and destroys it.
		$this->assertSame([], $_SESSION);

		// Restart the session so subsequent tests work.
		@session_start();
		$_SESSION = [];
	}

	public function testIsAdminLoggedInSetsUserAgentHashOnFirstCall(): void {
		$this->assertFalse(isset($_SESSION['HTTP_USER_AGENT']));
		SecurityUtil::isAdminLoggedIn();
		$this->assertSame(md5('PHPUnit-Test-Agent/1.0'), $_SESSION['HTTP_USER_AGENT']);
	}

	public function testLogoutAdminClearsSession(): void {
		$_SESSION['valid'] = true;
		$_SESSION['data'] = 'something';
		SecurityUtil::logoutAdmin();
		$this->assertSame([], $_SESSION);
		// Restart the session for subsequent tests.
		@session_start();
		$_SESSION = [];
	}
}
