<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FrontMessage.
 */
final class FrontMessageTest extends TestCaseBase {
	public function testConstructorStoresInfoType(): void {
		$m = new FrontMessage(MESSAGE_TYPE_INFO, 'Title', 'Body');
		$this->assertSame(MESSAGE_TYPE_INFO, $m->type);
		$this->assertSame('Title', $m->title);
		$this->assertSame('Body', $m->message);
	}

	public function testConstructorStoresSuccessType(): void {
		$m = new FrontMessage(MESSAGE_TYPE_SUCCESS, 't', 'b');
		$this->assertSame(MESSAGE_TYPE_SUCCESS, $m->type);
	}

	public function testConstructorStoresWarningType(): void {
		$m = new FrontMessage(MESSAGE_TYPE_WARNING, 't', 'b');
		$this->assertSame(MESSAGE_TYPE_WARNING, $m->type);
	}

	public function testConstructorStoresErrorType(): void {
		$m = new FrontMessage(MESSAGE_TYPE_ERROR, 't', 'b');
		$this->assertSame(MESSAGE_TYPE_ERROR, $m->type);
	}

	public function testConstructorThrowsOnInvalidType(): void {
		$this->expectException(\Exception::class);
		new FrontMessage('invalid', 't', 'b');
	}
}
