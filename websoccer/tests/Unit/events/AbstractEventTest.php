<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AbstractEvent (via a concrete stub subclass).
 */
final class AbstractEventTest extends TestCaseBase {
	public function testConstructorAssignsContextProperties(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$event = new TestableEvent($ws, $db, $i18n);

		$this->assertSame($ws, $event->websoccer);
		$this->assertSame($db, $event->db);
		$this->assertSame($i18n, $event->i18n);
	}
}

class TestableEvent extends AbstractEvent {
}
