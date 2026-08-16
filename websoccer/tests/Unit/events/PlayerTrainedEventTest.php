<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for PlayerTrainedEvent.
 */
final class PlayerTrainedEventTest extends TestCaseBase {
	public function testConstructorAssignsAllProperties(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$effectFreshness = 1;
		$effectTechnique = 2;
		$effectStamina = 3;
		$effectSatisfaction = 4;

		$event = new PlayerTrainedEvent(
			$ws, $db, $i18n,
			101, 202, 303,
			$effectFreshness, $effectTechnique, $effectStamina, $effectSatisfaction
		);

		$this->assertSame(101, $event->playerId);
		$this->assertSame(202, $event->teamId);
		$this->assertSame(303, $event->trainerId);
		$this->assertSame(1, $event->effectFreshness);
		$this->assertSame(2, $event->effectTechnique);
		$this->assertSame(3, $event->effectStamina);
		$this->assertSame(4, $event->effectSatisfaction);
	}

	public function testEffectReferencesAreMutable(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$effectFreshness = 0;
		$effectTechnique = 0;
		$effectStamina = 0;
		$effectSatisfaction = 0;

		$event = new PlayerTrainedEvent(
			$ws, $db, $i18n, 1, 2, 3,
			$effectFreshness, $effectTechnique, $effectStamina, $effectSatisfaction
		);

		$event->effectFreshness += 5;
		$event->effectSatisfaction += 2;

		$this->assertSame(5, $effectFreshness);
		$this->assertSame(2, $effectSatisfaction);
		$this->assertSame(0, $effectTechnique);
		$this->assertSame(0, $effectStamina);
	}
}
