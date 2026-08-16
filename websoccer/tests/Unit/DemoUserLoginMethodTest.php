<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DemoUserLoginMethod.
 */
final class DemoUserLoginMethodTest extends TestCaseBase {
	public function testAuthenticateWithUsernameThrowsException(): void {
		$method = new DemoUserLoginMethod(
			$this->mockWebsoccer(['db_prefix' => 'ws']),
			$this->mockDb()
		);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Unsupported action.');
		$method->authenticateWithUsername('johndoe', 'password');
	}

	public function testImplementsIUserLoginMethod(): void {
		$this->assertTrue(
			is_subclass_of(DemoUserLoginMethod::class, IUserLoginMethod::class)
		);
	}
}
