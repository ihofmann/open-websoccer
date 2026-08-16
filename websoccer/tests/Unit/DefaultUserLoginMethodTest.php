<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DefaultUserLoginMethod.
 */
final class DefaultUserLoginMethodTest extends TestCaseBase {
	private function makeMethod(\DbConnection $db): DefaultUserLoginMethod {
		return new DefaultUserLoginMethod(
			$this->mockWebsoccer(['db_prefix' => 'ws']),
			$db
		);
	}

	public function testAuthenticateWithUsernameReturnsUserIdOnCorrectPassword(): void {
		$salt = 'abcd';
		$hashedPassword = SecurityUtil::hashPassword('secret123', $salt);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([[
				'id' => '42',
				'passwort' => $hashedPassword,
				'passwort_neu' => '',
				'passwort_salt' => $salt,
			]])
		);
		$db->expects($this->never())->method('queryUpdate');

		$method = $this->makeMethod($db);
		$this->assertSame('42', $method->authenticateWithUsername('johndoe', 'secret123'));
	}

	public function testAuthenticateWithUsernameReturnsFalseForUnknownUser(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['id' => null, 'passwort' => '', 'passwort_neu' => '', 'passwort_salt' => '']])
		);

		$method = $this->makeMethod($db);
		$this->assertFalse($method->authenticateWithUsername('nobody', 'pass'));
	}

	public function testAuthenticateWithUsernameReturnsFalseForWrongPassword(): void {
		$salt = 'abcd';
		$hashedPassword = SecurityUtil::hashPassword('correctpass', $salt);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([[
				'id' => '42',
				'passwort' => $hashedPassword,
				'passwort_neu' => '',
				'passwort_salt' => $salt,
			]])
		);

		$method = $this->makeMethod($db);
		$this->assertFalse($method->authenticateWithUsername('johndoe', 'wrongpass'));
	}

	public function testAuthenticateWithEmailUpdatesPasswordWhenNewPasswordMatches(): void {
		$salt = 'abcd';
		$newHashedPassword = SecurityUtil::hashPassword('newpass123', $salt);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([[
				'id' => '42',
				'passwort' => 'oldhash',
				'passwort_neu' => $newHashedPassword,
				'passwort_salt' => $salt,
			]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) use ($newHashedPassword) {
					return $cols['passwort'] === $newHashedPassword
						&& $cols['passwort_neu_angefordert'] === 0
						&& $cols['passwort_neu'] === '';
				}),
				'ws_user',
				'id = %d',
				'42'
			);

		$method = $this->makeMethod($db);
		$this->assertSame('42', $method->authenticateWithEmail('user@example.com', 'newpass123'));
	}

	public function testAuthenticateWithEmailReturnsFalseForUnknownEmail(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['id' => null, 'passwort' => '', 'passwort_neu' => '', 'passwort_salt' => '']])
		);

		$method = $this->makeMethod($db);
		$this->assertFalse($method->authenticateWithEmail('nobody@example.com', 'pass'));
	}
}
