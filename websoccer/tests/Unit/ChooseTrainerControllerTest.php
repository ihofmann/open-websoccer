<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ChooseTrainerController.
 */
final class ChooseTrainerControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = [], array $cachedRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($selectRowsByTable) {
				foreach ($selectRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($cachedRowsByTable) {
				foreach ($cachedRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $rows;
					}
				}
				return [];
			}
		);
		return $db;
	}

	private function makeUserWithClub(?int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		if ($clubId !== null) {
			$user->setClubId($clubId);
		}
		return $user;
	}

	private function baseConfig(): array {
		return ['db_prefix' => 'ws', 'no_transactions_for_teams_without_user' => false];
	}

	public function testExecuteActionThrowsWhenUserHasNoTeam(): void {
		$user = $this->makeUser(['id' => 1]); // no club
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseTrainerController(
			$this->mockI18n(['feature_requires_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['id' => 3, 'units' => 2]);
	}

	public function testExecuteActionThrowsWhenExistingTrainingUnits(): void {
		$db = $this->makeDb(['_training_unit' => [['hits' => 1]]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseTrainerController(
			$this->mockI18n(['training_choose_trainer_err_existing_units' => 'existing units']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('existing units');
		$controller->executeAction(['id' => 3, 'units' => 2]);
	}

	public function testExecuteActionThrowsWhenTrainerNotFound(): void {
		$db = $this->makeDb(['_training_unit' => [['hits' => 0]]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseTrainerController($this->mockI18n(), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid ID');
		$controller->executeAction(['id' => 3, 'units' => 2]);
	}

	public function testExecuteActionThrowsWhenTooExpensive(): void {
		$db = $this->makeDb(
			['_training_unit' => [['hits' => 0]], '_trainer' => [['id' => 3, 'salary' => 1000, 'premiumfee' => 0, 'name' => 'Coach']]],
			['_verein AS C' => [['team_id' => 1, 'team_name' => 'My', 'team_budget' => 500, 'user_id' => 1]]]
		);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseTrainerController(
			$this->mockI18n(['training_choose_trainer_err_too_expensive' => 'too expensive']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('too expensive');
		$controller->executeAction(['id' => 3, 'units' => 2]);
	}

	public function testExecuteActionCreatesTrainingUnitsAndReturnsTraining(): void {
		$db = $this->makeDb(
			['_training_unit' => [['hits' => 0]], '_trainer' => [['id' => 3, 'salary' => 100, 'premiumfee' => 0, 'name' => 'Coach']]],
			['_verein AS C' => [['team_id' => 1, 'team_name' => 'My', 'team_budget' => 10000, 'user_id' => 1]]]
		);
		$inserts = [];
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserts) {
			$inserts[] = ['columns' => $columns, 'fromTable' => $fromTable];
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseTrainerController(
			$this->mockI18n(['saved_message_title' => 'saved']), $ws, $db);

		$this->assertSame('training', $controller->executeAction(['id' => 3, 'units' => 2]));
		// two training units inserted (plus bank transaction)
		$unitInserts = array_filter($inserts, fn($i) => $i['fromTable'] === 'ws_training_unit');
		$this->assertCount(2, $unitInserts);
	}
}
