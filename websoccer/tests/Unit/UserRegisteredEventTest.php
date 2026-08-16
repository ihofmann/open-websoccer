<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for UserRegisteredEvent.
 */
final class UserRegisteredEventTest extends TestCaseBase {
	public function testConstructorAssignsAllProperties(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$event = new UserRegisteredEvent($ws, $db, $i18n, 42, 'johndoe', 'john@example.com');

		$this->assertSame(42, $event->userId);
		$this->assertSame('johndoe', $event->username);
		$this->assertSame('john@example.com', $event->email);
	}

	public function testConstructorAcceptsNullUsernameAndEmail(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$event = new UserRegisteredEvent($ws, $db, $i18n, 7, null, null);

		$this->assertSame(7, $event->userId);
		$this->assertNull($event->username);
		$this->assertNull($event->email);
	}

	public function testEventExtendsAbstractEvent(): void {
		$this->assertTrue(
			is_subclass_of(UserRegisteredEvent::class, AbstractEvent::class)
		);
	}
}
