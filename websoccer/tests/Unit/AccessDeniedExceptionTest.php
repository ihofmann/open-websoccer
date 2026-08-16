<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AccessDeniedException.
 */
final class AccessDeniedExceptionTest extends TestCaseBase {
	public function testConstructorStoresMessage(): void {
		$e = new AccessDeniedException('Access denied');
		$this->assertSame('Access denied', $e->getMessage());
	}

	public function testConstructorDefaultsCodeToZero(): void {
		$e = new AccessDeniedException('No access');
		$this->assertSame(0, $e->getCode());
	}

	public function testConstructorStoresCustomCode(): void {
		$e = new AccessDeniedException('Forbidden', 403);
		$this->assertSame(403, $e->getCode());
		$this->assertSame('Forbidden', $e->getMessage());
	}

	public function testExtendsException(): void {
		$e = new AccessDeniedException('msg');
		$this->assertInstanceOf(\Exception::class, $e);
	}

	public function testCanBeThrownAndCaught(): void {
		try {
			throw new AccessDeniedException('denied', 1);
			$this->fail('Exception was not thrown.');
		} catch (AccessDeniedException $e) {
			$this->assertSame('denied', $e->getMessage());
			$this->assertSame(1, $e->getCode());
		}
	}
}
