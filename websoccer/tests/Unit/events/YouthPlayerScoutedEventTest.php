<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for YouthPlayerScoutedEvent.
 */
final class YouthPlayerScoutedEventTest extends TestCaseBase {
	public function testConstructorAssignsAllProperties(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$event = new YouthPlayerScoutedEvent($ws, $db, $i18n, 30, 40, 50);

		$this->assertSame(30, $event->teamId);
		$this->assertSame(40, $event->scoutId);
		$this->assertSame(50, $event->playerId);
	}

	public function testEventExtendsAbstractEvent(): void {
		$this->assertTrue(
			is_subclass_of(YouthPlayerScoutedEvent::class, AbstractEvent::class)
		);
	}
}
