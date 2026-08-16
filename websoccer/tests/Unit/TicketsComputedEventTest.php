<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for TicketsComputedEvent.
 */
final class TicketsComputedEventTest extends TestCaseBase {
	public function testConstructorAssignsMatchAndStadiumId(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();
		$home = new SimulationTeam(1);
		$guest = new SimulationTeam(2);
		$match = new SimulationMatch(50, $home, $guest, 1);

		$rateStands = 0.5;
		$rateSeats = 0.6;
		$rateStandsGrand = 0.4;
		$rateSeatsGrand = 0.3;
		$rateVip = 0.2;

		$event = new TicketsComputedEvent(
			$ws, $db, $i18n, $match, 77,
			$rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip
		);

		$this->assertSame($match, $event->match);
		$this->assertSame(77, $event->stadiumId);
	}

	public function testRateReferencesAreMutable(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();
		$match = new SimulationMatch(1, new SimulationTeam(1), new SimulationTeam(2), 1);

		$rateStands = 0.5;
		$rateSeats = 0.6;
		$rateStandsGrand = 0.4;
		$rateSeatsGrand = 0.3;
		$rateVip = 0.2;

		$event = new TicketsComputedEvent(
			$ws, $db, $i18n, $match, 1,
			$rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip
		);

		$event->rateSeats = 0.9;
		$event->rateVip = 0.1;

		$this->assertSame(0.9, $rateSeats);
		$this->assertSame(0.1, $rateVip);
	}
}
