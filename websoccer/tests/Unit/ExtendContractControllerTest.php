<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for ExtendContractController.
 */
final class ExtendContractControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'players_aging' => 'season',
			'transfermarket_computed_marketvalue' => FALSE,
		];
	}

	/**
	 * Builds a DbConnection mock dispatching queryCachedSelect / querySelect by
	 * table name and (for the two _spieler selects) by the aggregate column.
	 */
	private function buildDb(array $player, array $team, int $avgSalary, int $salarySum, ?array $inactivity): \DbConnection {
		$db = $this->createMock(\DbConnection::class);

		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable) use ($player, $team) {
				if (strpos($fromTable, '_spieler AS P') !== false) {
					return [$player];
				}
				if (strpos($fromTable, '_verein AS C') !== false) {
					return [$team];
				}
				return [];
			}
		);

		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable) use ($avgSalary, $salarySum, $inactivity) {
				if (strpos($fromTable, '_user_inactivity') !== false) {
					return $this->dbResult($inactivity !== null ? [$inactivity] : []);
				}
				if (strpos($fromTable, '_spieler') !== false) {
					// Distinguish the AVG query from the SUM query by the column expression.
					if (is_string($columns) && strpos($columns, 'AVG') !== false) {
						return $this->dbResult([['average_salary' => $avgSalary]]);
					}
					return $this->dbResult([['salary_sum' => $salarySum]]);
				}
				return $this->dbResult([]);
			}
		);

		return $db;
	}

	public function testThrowsWhenPlayerIsNotOwn(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$player = $this->playerRow(['player_id' => 7, 'team_id' => 2, 'player_strength_satisfaction' => 80]);
		$db = $this->buildDb($player, $this->teamRow(), 1000, 0, null);
		$db->expects($this->never())->method('queryUpdate');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('nice try');

		$controller = new ExtendContractController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7, 'salary' => 2000, 'goal_bonus' => 200, 'matches' => 20]);
	}

	public function testThrowsWhenPlayerAlreadyOnMarket(): void {
		$i18n = $this->mockI18n(['sell_player_already_on_list' => 'on list']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$player = $this->playerRow([
			'player_id' => 7, 'team_id' => 1, 'player_strength_satisfaction' => 80,
			'player_transfermarket' => 1,
		]);
		$db = $this->buildDb($player, $this->teamRow(), 1000, 0, null);
		$db->expects($this->never())->method('queryUpdate');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('on list');

		$controller = new ExtendContractController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7, 'salary' => 2000, 'goal_bonus' => 200, 'matches' => 20]);
	}

	public function testThrowsWhenSalaryTooLowAndDecreasesSatisfaction(): void {
		$i18n = $this->mockI18n(['extend-contract_salary_too_low' => 'too low']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$player = $this->playerRow([
			'player_id' => 7, 'team_id' => 1, 'player_strength_satisfaction' => 80,
			'player_transfermarket' => 0, 'player_contract_salary' => 1000,
			'player_contract_goalbonus' => 100, 'player_strength' => 50,
		]);
		$db = $this->buildDb($player, $this->teamRow(), 1000, 0, null);

		$decreased = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$decreased) {
			$decreased = [$columns, $fromTable, $parameters];
		});

		$controller = new ExtendContractController($i18n, $ws, $db);

		$thrown = null;
		try {
			// salary 1100 is above the current salary (1000) but below the computed minimum (1200).
			$controller->executeAction(['id' => 7, 'salary' => 1100, 'goal_bonus' => 200, 'matches' => 20]);
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertNotNull($thrown);
		$this->assertSame('too low', $thrown->getMessage());
		// decreaseSatisfaction: 80 - 10 = 70.
		$this->assertNotNull($decreased);
		$this->assertSame(70, $decreased[0]['w_zufriedenheit']);
		$this->assertSame('ws_spieler', $decreased[1]);
	}

	public function testExtendsContractAndReturnsNull(): void {
		$i18n = $this->mockI18n(['extend-contract_success' => 'extended']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$player = $this->playerRow([
			'player_id' => 7, 'team_id' => 1, 'player_strength_satisfaction' => 80,
			'player_transfermarket' => 0, 'player_contract_salary' => 1000,
			'player_contract_goalbonus' => 100, 'player_strength' => 50,
		]);
		$team = $this->teamRow(['team_budget' => 1000000]);
		$inactivity = ['id' => 3, 'login' => 0, 'login_check' => 0, 'tactics' => 0, 'transfer' => 0,
			'transfer_check' => 0, 'contractextensions' => 0];
		$db = $this->buildDb($player, $team, 1000, 1000, $inactivity);

		$updates = [];
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updates) {
			$updates[] = ['columns' => $columns, 'table' => $fromTable, 'params' => $parameters];
		});

		$controller = new ExtendContractController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction([
			'id' => 7, 'salary' => 2000, 'goal_bonus' => 200, 'matches' => 20,
		]));

		$tables = array_column($updates, 'table');
		// Player contract update + inactivity reset update.
		$this->assertContains('ws_spieler', $tables);
		$this->assertContains('ws_user_inactivity', $tables);

		// Find the player update and verify the new contract values.
		$playerUpdate = null;
		foreach ($updates as $u) {
			if ($u['table'] === 'ws_spieler') {
				$playerUpdate = $u;
				break;
			}
		}
		$this->assertSame(2000, $playerUpdate['columns']['vertrag_gehalt']);
		$this->assertSame(200, $playerUpdate['columns']['vertrag_torpraemie']);
		$this->assertSame(20, $playerUpdate['columns']['vertrag_spiele']);
		// satisfaction increases by 10, capped at 100.
		$this->assertSame(90, $playerUpdate['columns']['w_zufriedenheit']);
	}
}
