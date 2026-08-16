<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for BookCampController.
 */
final class BookCampControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'date_format' => 'Y-m-d',
			'trainingcamp_min_days' => 1,
			'trainingcamp_max_days' => 14,
			'trainingcamp_booking_max_days_in_future' => 365,
		];
	}

	public function testThrowsWhenUserHasNoClub(): void {
		$i18n = $this->mockI18n(['feature_requires_team' => 'requires team']);
		$ws = $this->mockWebsoccer($this->config());
		// Guest user -> getClubId() returns null -> teamId < 1.
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryInsert');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');

		$controller = new BookCampController($i18n, $ws, $db);
		$controller->executeAction(['id' => 5, 'days' => 5, 'start_date' => '2099-01-01']);
	}

	public function testThrowsWhenDaysOutOfRange(): void {
		$i18n = $this->mockI18n(['trainingcamp_booking_err_invaliddays' => 'invalid %s %s']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow(['team_budget' => 1000000])]],
			['_trainingslager' => [['id' => 5, 'name' => 'Camp', 'land' => 'DE', 'preis_spieler_tag' => 100,
				'p_staerke' => 1, 'p_technik' => 1, 'p_kondition' => 1, 'p_frische' => 1, 'p_zufriedenheit' => 1]]]
		);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid 1 14');

		$controller = new BookCampController($i18n, $ws, $db);
		// 20 days exceeds the configured maximum of 14.
		$controller->executeAction(['id' => 5, 'days' => 20, 'start_date' => '2099-01-01']);
	}

	public function testBooksCampAndReturnsTrainingcamp(): void {
		$now = 1000000;
		$i18n = $this->mockI18n(['trainingcamp_booking_success' => 'booked']);
		$ws = $this->mockWebsoccerAt($now, $this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));

		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow(['team_budget' => 1000000])]],
			[
				'_trainingslager' => [['id' => 5, 'name' => 'Camp', 'country' => 'DE', 'costs' => 100,
					'effect_strength' => 1, 'effect_strength_technique' => 1, 'effect_strength_stamina' => 1,
					'effect_strength_freshness' => 1, 'effect_strength_satisfaction' => 1]],
				'_trainingslager_belegung AS B' => [],
				'_spiel AS M' => [],
				'_spieler' => [['id' => 1, 'position' => 'Torwart']],
			]
		);

		$inserts = [];
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserts) {
			$inserts[] = $fromTable;
		});

		// start date 30 days in the future, within the configured booking horizon.
		$startDate = date('Y-m-d', $now + 30 * 86400);

		$controller = new BookCampController($i18n, $ws, $db);
		$this->assertSame('trainingcamp', $controller->executeAction([
			'id' => 5, 'days' => 5, 'start_date' => $startDate,
		]));

		// Two inserts: bank-account statement and the camp booking itself.
		$this->assertContains('ws_konto', $inserts);
		$this->assertContains('ws_trainingslager_belegung', $inserts);
	}
}
