<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ValidationException.
 */
final class ValidationExceptionTest extends TestCaseBase {
	public function testConstructorStoresMessages(): void {
		$messages = ['Field is required', 'Invalid format'];
		$e = new ValidationException($messages);
		$this->assertSame($messages, $e->getMessages());
	}

	public function testConstructorSetsDefaultMessage(): void {
		$e = new ValidationException(['error']);
		$this->assertSame('Validation failed', $e->getMessage());
	}

	public function testGetMessagesReturnsEmptyArrayForEmptyInput(): void {
		$e = new ValidationException([]);
		$this->assertSame([], $e->getMessages());
	}

	public function testExtendsException(): void {
		$e = new ValidationException(['msg']);
		$this->assertInstanceOf(\Exception::class, $e);
	}

	public function testGetMessagesReturnsSameArrayReference(): void {
		$messages = ['a', 'b'];
		$e = new ValidationException($messages);
		$this->assertSame($messages, $e->getMessages());
	}
}
