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

	public function testHashPasswordProducesBcryptHash(): void {
		$hash = SecurityUtil::hashPassword('secret', 'salt');
		$this->assertStringStartsWith('$2y$', $hash);
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

	public function testVerifyPasswordAcceptsCorrectPassword(): void {
		$hash = SecurityUtil::hashPassword('password', 'salt');
		$this->assertTrue(SecurityUtil::verifyPassword('password', 'salt', $hash));
	}

	public function testVerifyPasswordRejectsWrongPassword(): void {
		$hash = SecurityUtil::hashPassword('password', 'salt');
		$this->assertFalse(SecurityUtil::verifyPassword('wrong', 'salt', $hash));
	}

	public function testVerifyPasswordAcceptsLegacySha256Hash(): void {
		$legacyHash = hash('sha256', 'salt' . hash('sha256', 'password'));
		$this->assertTrue(SecurityUtil::verifyPassword('password', 'salt', $legacyHash));
	}

	public function testVerifyPasswordRejectsWrongPasswordForLegacyHash(): void {
		$legacyHash = hash('sha256', 'salt' . hash('sha256', 'password'));
		$this->assertFalse(SecurityUtil::verifyPassword('wrong', 'salt', $legacyHash));
	}

	public function testNeedsRehashReturnsTrueForLegacyHash(): void {
		$legacyHash = hash('sha256', 'salt' . hash('sha256', 'password'));
		$this->assertTrue(SecurityUtil::needsRehash($legacyHash));
	}

	public function testNeedsRehashReturnsFalseForFreshBcryptHash(): void {
		$hash = SecurityUtil::hashPassword('password', 'salt');
		$this->assertFalse(SecurityUtil::needsRehash($hash));
	}

	public function testGeneratePasswordReturnsStringOfLength12(): void {
		$this->assertSame(12, strlen(SecurityUtil::generatePassword()));
	}

	public function testGeneratePasswordUsesCharsetCharactersOnly(): void {
		$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$allowed = str_split($charset);
		// Generate several passwords to increase confidence.
		for ($i = 0; $i < 20; $i++) {
			$pw = SecurityUtil::generatePassword();
			foreach (str_split($pw) as $char) {
				$this->assertContains($char, $allowed, 'Character not in charset: ' . $char);
			}
		}
	}

	public function testGeneratePasswordSaltReturnsStringOfLength5(): void {
		$salt = SecurityUtil::generatePasswordSalt();
		$this->assertSame(5, strlen($salt));
	}

	public function testGenerateSessionTokenReturns64CharHex(): void {
		$token = SecurityUtil::generateSessionToken(42, 'salt');
		$this->assertSame(64, strlen($token));
		$this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
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

	public function testGenerateSessionTokenDiffersOnRepeatedCalls(): void {
		$_SESSION['HTTP_USER_AGENT'] = 'agent-hash';
		$a = SecurityUtil::generateSessionToken(1, 'salt');
		$b = SecurityUtil::generateSessionToken(1, 'salt');
		$this->assertNotSame($a, $b);
	}

	public function testIsAdminLoggedInReturnsFalseWhenNoValidSessionSet(): void {
		// First call sets the user agent hash but valid is not set.
		$result = SecurityUtil::isAdminLoggedIn();
		$this->assertFalse($result);
	}

	public function testIsAdminLoggedInReturnsTrueWhenValidSessionSet(): void {
		$_SESSION['HTTP_USER_AGENT'] = hash('sha256', 'PHPUnit-Test-Agent/1.0');
		$_SESSION['valid'] = true;
		$this->assertTrue(SecurityUtil::isAdminLoggedIn());
	}

	public function testIsAdminLoggedInReturnsFalseWhenValidIsFalse(): void {
		$_SESSION['HTTP_USER_AGENT'] = hash('sha256', 'PHPUnit-Test-Agent/1.0');
		$_SESSION['valid'] = false;
		$this->assertFalse(SecurityUtil::isAdminLoggedIn());
	}

	public function testIsAdminLoggedInLogsOutOnUserAgentMismatch(): void {
		$_SESSION['HTTP_USER_AGENT'] = hash('sha256', 'different-agent');
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
		$this->assertSame(hash('sha256', 'PHPUnit-Test-Agent/1.0'), $_SESSION['HTTP_USER_AGENT']);
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
