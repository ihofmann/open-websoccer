<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for StadiumEnvironmentPlugin.
 */
final class StadiumEnvironmentPluginTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer {
		return $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'youth_scouting_min_strength' => 1,
			'youth_scouting_max_strength' => 90,
		]);
	}

	// ---- addTrainingBonus ----

	public function testAddTrainingBonusIncreasesEffectsByBonus(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => 5]]));

		$effectFreshness = 10;
		$effectTechnique = 5;
		$effectStamina = 3;
		$effectSatisfaction = 7;

		$event = new PlayerTrainedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			1, 100, 200,
			$effectFreshness, $effectTechnique, $effectStamina, $effectSatisfaction
		);

		StadiumEnvironmentPlugin::addTrainingBonus($event);

		$this->assertSame(12, $effectSatisfaction);
		$this->assertSame(15, $effectFreshness);
	}

	public function testAddTrainingBonusWithZeroBonusDoesNotChangeEffects(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => 0]]));

		$effectFreshness = 10;
		$effectTechnique = 5;
		$effectStamina = 3;
		$effectSatisfaction = 7;

		$event = new PlayerTrainedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			1, 100, 200,
			$effectFreshness, $effectTechnique, $effectStamina, $effectSatisfaction
		);

		StadiumEnvironmentPlugin::addTrainingBonus($event);

		$this->assertSame(7, $effectSatisfaction);
		$this->assertSame(10, $effectFreshness);
	}

	// ---- addYouthPlayerSkillBonus ----

	public function testAddYouthPlayerSkillBonusWithZeroBonusDoesNotQueryPlayer(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => 0]]));
		$db->expects($this->never())->method('queryUpdate');

		$event = new YouthPlayerScoutedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			30, 40, 50
		);

		StadiumEnvironmentPlugin::addYouthPlayerSkillBonus($event);
	}

	public function testAddYouthPlayerSkillBonusUpdatesStrengthWithBonus(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['attrSum' => 3]]),
			$this->dbResult([['strength' => 60]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['strength'] === 63;
				}),
				'ws_youthplayer',
				'id = %d',
				50
			);

		$event = new YouthPlayerScoutedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			30, 40, 50
		);

		StadiumEnvironmentPlugin::addYouthPlayerSkillBonus($event);
	}

	public function testAddYouthPlayerSkillBonusClampsToMaxStrength(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['attrSum' => 20]]),
			$this->dbResult([['strength' => 80]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['strength'] === 90;
				}),
				'ws_youthplayer',
				'id = %d',
				50
			);

		$event = new YouthPlayerScoutedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			30, 40, 50
		);

		StadiumEnvironmentPlugin::addYouthPlayerSkillBonus($event);
	}

	public function testAddYouthPlayerSkillBonusClampsToMinStrength(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['attrSum' => -20]]),
			$this->dbResult([['strength' => 5]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['strength'] === 1;
				}),
				'ws_youthplayer',
				'id = %d',
				50
			);

		$event = new YouthPlayerScoutedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			30, 40, 50
		);

		StadiumEnvironmentPlugin::addYouthPlayerSkillBonus($event);
	}

	public function testAddYouthPlayerSkillBonusDoesNotUpdateWhenStrengthUnchanged(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([['attrSum' => 0]]),
			$this->dbResult([['strength' => 60]])
		);
		// attrSum is 0, so bonus == 0, no queryUpdate
		$db->expects($this->never())->method('queryUpdate');

		$event = new YouthPlayerScoutedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			30, 40, 50
		);

		StadiumEnvironmentPlugin::addYouthPlayerSkillBonus($event);
	}

	// ---- addTicketsBonus ----

	public function testAddTicketsBonusIncreasesRatesByBonusPercentage(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => 10]]));

		$home = new SimulationTeam(5);
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);

		$rateStands = 0.5;
		$rateSeats = 0.6;
		$rateStandsGrand = 0.4;
		$rateSeatsGrand = 0.3;
		$rateVip = 0.2;

		$event = new TicketsComputedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			$match, 77,
			$rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip
		);

		StadiumEnvironmentPlugin::addTicketsBonus($event);

		$this->assertEqualsWithDelta(0.6, $rateStands, 1e-9);
		$this->assertEqualsWithDelta(0.7, $rateSeats, 1e-9);
		$this->assertEqualsWithDelta(0.5, $rateStandsGrand, 1e-9);
		$this->assertEqualsWithDelta(0.4, $rateSeatsGrand, 1e-9);
		$this->assertEqualsWithDelta(0.3, $rateVip, 1e-9);
	}

	public function testAddTicketsBonusWithZeroBonusDoesNotChangeRates(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => 0]]));

		$home = new SimulationTeam(5);
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);

		$rateStands = 0.5;
		$rateSeats = 0.6;
		$rateStandsGrand = 0.4;
		$rateSeatsGrand = 0.3;
		$rateVip = 0.2;

		$event = new TicketsComputedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			$match, 77,
			$rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip
		);

		StadiumEnvironmentPlugin::addTicketsBonus($event);

		$this->assertSame(0.5, $rateStands);
		$this->assertSame(0.6, $rateSeats);
		$this->assertSame(0.4, $rateStandsGrand);
		$this->assertSame(0.3, $rateSeatsGrand);
		$this->assertSame(0.2, $rateVip);
	}

	public function testAddTicketsBonusClampsToUpperBound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => 50]]));

		$home = new SimulationTeam(5);
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);

		$rateStands = 0.8;
		$rateSeats = 0.9;
		$rateStandsGrand = 0.7;
		$rateSeatsGrand = 0.6;
		$rateVip = 0.5;

		$event = new TicketsComputedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			$match, 77,
			$rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip
		);

		StadiumEnvironmentPlugin::addTicketsBonus($event);

		$this->assertSame(1.0, $rateStands);
		$this->assertSame(1.0, $rateSeats);
		$this->assertSame(1.0, $rateStandsGrand);
		$this->assertSame(1.0, $rateSeatsGrand);
		$this->assertSame(1.0, $rateVip);
	}

	public function testAddTicketsBonusClampsToLowerBound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['attrSum' => -50]]));

		$home = new SimulationTeam(5);
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);

		$rateStands = 0.2;
		$rateSeats = 0.1;
		$rateStandsGrand = 0.3;
		$rateSeatsGrand = 0.4;
		$rateVip = 0.1;

		$event = new TicketsComputedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(),
			$match, 77,
			$rateStands, $rateSeats, $rateStandsGrand, $rateSeatsGrand, $rateVip
		);

		StadiumEnvironmentPlugin::addTicketsBonus($event);

		$this->assertSame(0.0, $rateStands);
		$this->assertSame(0.0, $rateSeats);
		$this->assertSame(0.0, $rateStandsGrand);
		$this->assertSame(0.0, $rateSeatsGrand);
		$this->assertSame(0.0, $rateVip);
	}

	// ---- creditAndDebitAfterHomeMatch ----

	public function testCreditAndDebitAfterHomeMatchSkipsFriendlies(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		$home = new SimulationTeam(5);
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);
		$match->type = 'Freundschaft';

		$event = new MatchCompletedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(), $match
		);

		StadiumEnvironmentPlugin::creditAndDebitAfterHomeMatch($event);
	}

	public function testCreditAndDebitAfterHomeMatchSkipsNationalTeams(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		$home = new SimulationTeam(5);
		$home->isNationalTeam = true;
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);
		$match->type = 'Liga';

		$event = new MatchCompletedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(), $match
		);

		StadiumEnvironmentPlugin::creditAndDebitAfterHomeMatch($event);
	}

	// ---- handleInjuriesAfterMatch ----

	public function testHandleInjuriesAfterMatchSkipsFriendlies(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		$home = new SimulationTeam(5);
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);
		$match->type = 'Freundschaft';

		$event = new MatchCompletedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(), $match
		);

		StadiumEnvironmentPlugin::handleInjuriesAfterMatch($event);
	}

	public function testHandleInjuriesAfterMatchSkipsNationalTeams(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		$home = new SimulationTeam(5);
		$home->isNationalTeam = true;
		$match = new SimulationMatch(1, $home, new SimulationTeam(6), 1);
		$match->type = 'Liga';

		$event = new MatchCompletedEvent(
			$this->makeWebsoccer(), $db, $this->mockI18n(), $match
		);

		StadiumEnvironmentPlugin::handleInjuriesAfterMatch($event);
	}
}
