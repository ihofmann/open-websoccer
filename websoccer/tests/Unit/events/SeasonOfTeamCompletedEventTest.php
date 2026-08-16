<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SeasonOfTeamCompletedEvent.
 */
final class SeasonOfTeamCompletedEventTest extends TestCaseBase {
	public function testConstructorAssignsAllProperties(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$event = new SeasonOfTeamCompletedEvent($ws, $db, $i18n, 55, 10, 3);

		$this->assertSame(55, $event->teamId);
		$this->assertSame(10, $event->seasonId);
		$this->assertSame(3, $event->rank);
	}

	public function testEventExtendsAbstractEvent(): void {
		$this->assertTrue(
			is_subclass_of(SeasonOfTeamCompletedEvent::class, AbstractEvent::class)
		);
	}
}
