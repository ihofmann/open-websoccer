<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for ExecuteTrainingController.
 */
final class ExecuteTrainingControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'training_min_hours_between_execution' => 1,
		];
	}

	public function testReturnsNullWhenUserHasNoClub(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new ExecuteTrainingController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1, 'focus' => 'FR', 'intensity' => 50]));
	}

	public function testThrowsWhenTrainingUnitInvalid(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// No training unit rows -> fetch_array() returns false -> id not set.
		$db = $this->makeDb([], ['_training_unit' => []]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid ID');

		$controller = new ExecuteTrainingController($i18n, $ws, $db);
		$controller->executeAction(['id' => 99, 'focus' => 'FR', 'intensity' => 50]);
	}

	public function testThrowsWhenUnitAlreadyExecuted(): void {
		$i18n = $this->mockI18n(['training_execute_err_already_executed' => 'already done']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb([], [
			'_training_unit' => [['id' => 1, 'date_executed' => 123456, 'trainer_id' => 2, 'team_id' => 1]],
		]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('already done');

		$controller = new ExecuteTrainingController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1, 'focus' => 'FR', 'intensity' => 50]);
	}

	public function testExecutesRegenerationTrainingAndReturnsNull(): void {
		$now = 1000000;
		$i18n = $this->mockI18n(['training_execute_success' => 'trained']);
		$ws = $this->mockWebsoccerAt($now, $this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));

		$db = $this->makeDb(
			['_spiel AS M' => []], // getLiveMatchByTeam -> no live match
			[
				'_training_unit' => [['id' => 1, 'date_executed' => 0, 'trainer_id' => 2, 'team_id' => 1]],
				'_trainingslager_belegung AS B' => [], // no camp bookings
				'_trainer' => [['id' => 2, 'p_stamina' => 50, 'p_technique' => 50]],
				'_spieler' => [[
					'id' => 10, 'firstname' => 'A', 'lastname' => 'B', 'pseudonym' => '',
					'matches_injured' => 0, 'position' => 'Torwart',
					'strength_freshness' => 50, 'strength_technic' => 50, 'strength_stamina' => 50,
					'strength_satisfaction' => 80,
				]],
			]
		);

		$updates = [];
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updates) {
			$updates[] = ['columns' => $columns, 'table' => $fromTable, 'params' => $parameters];
		});

		$controller = new ExecuteTrainingController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1, 'focus' => 'FR', 'intensity' => 50]));

		// One player update + one training-unit execution update.
		$tables = array_column($updates, 'table');
		$this->assertContains('ws_spieler', $tables);
		$this->assertContains('ws_training_unit', $tables);

		// The training-unit update records the execution timestamp and chosen focus/intensity.
		$unitUpdate = null;
		foreach ($updates as $u) {
			if ($u['table'] === 'ws_training_unit') {
				$unitUpdate = $u;
				break;
			}
		}
		$this->assertSame($now, $unitUpdate['columns']['date_executed']);
		$this->assertSame('FR', $unitUpdate['columns']['focus']);
		$this->assertSame(50, $unitUpdate['columns']['intensity']);
	}
}
