<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for YouthPlayerPlayedEvent.
 */
final class YouthPlayerPlayedEventTest extends TestCaseBase {
	private function makePlayer(): SimulationPlayer {
		return new SimulationPlayer(15, new SimulationTeam(3), 'Sturm', 'Sturm', 3.0, 17, 60, 50, 70, 80, 65);
	}

	public function testConstructorAssignsPlayerAndStrengthChange(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();
		$player = $this->makePlayer();
		$strengthChange = 2;

		$event = new YouthPlayerPlayedEvent($ws, $db, $i18n, $player, $strengthChange);

		$this->assertSame($player, $event->player);
		$this->assertSame(2, $event->strengthChange);
	}

	public function testStrengthChangeReferenceIsMutable(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();
		$player = $this->makePlayer();
		$strengthChange = 0;

		$event = new YouthPlayerPlayedEvent($ws, $db, $i18n, $player, $strengthChange);

		$event->strengthChange = 5;
		$this->assertSame(5, $strengthChange);
	}

	public function testEventExtendsAbstractEvent(): void {
		$this->assertTrue(
			is_subclass_of(YouthPlayerPlayedEvent::class, AbstractEvent::class)
		);
	}
}
