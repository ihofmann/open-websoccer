<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for MatchCompletedEvent.
 */
final class MatchCompletedEventTest extends TestCaseBase {
	public function testConstructorAssignsContextAndMatch(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();
		$home = new SimulationTeam(1);
		$guest = new SimulationTeam(2);
		$match = new SimulationMatch(99, $home, $guest, 1);

		$event = new MatchCompletedEvent($ws, $db, $i18n, $match);

		$this->assertSame($ws, $event->websoccer);
		$this->assertSame($db, $event->db);
		$this->assertSame($i18n, $event->i18n);
		$this->assertSame($match, $event->match);
		$this->assertSame(99, $event->match->id);
	}

	public function testEventExtendsAbstractEvent(): void {
		$this->assertTrue(
			is_subclass_of(MatchCompletedEvent::class, AbstractEvent::class)
		);
	}
}
