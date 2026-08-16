<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FirePlayerController.
 */
final class FirePlayerControllerTest extends TestCaseBase {
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

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId($clubId);
		return $user;
	}

	private function baseConfig(): array {
		return ['enable_player_resignation' => true, 'db_prefix' => 'ws',
			'transfermarket_min_teamsize' => 11, 'player_resignation_compensation_matches' => 0,
			'players_aging' => 'age', 'transfermarket_computed_marketvalue' => false];
	}

	private function playerRow(array $override = []): array {
		return array_merge([
			'player_id' => 5, 'team_id' => 1, 'player_firstname' => 'A', 'player_lastname' => 'B',
			'player_contract_salary' => 10, 'player_position' => 'Torwart',
			'player_nationality' => 'Deutschland', 'matches_info' => '0;0', 'player_marketvalue' => 1000,
		], $override);
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['enable_player_resignation' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FirePlayerController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenPlayerIsNotOwn(): void {
		$db = $this->makeDb([], ['_spieler AS P' => [$this->playerRow(['team_id' => 2])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FirePlayerController($this->mockI18n(), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('nice try');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionThrowsWhenTeamSizeTooSmall(): void {
		$db = $this->makeDb(['_spieler' => [['number' => 5]]], ['_spieler AS P' => [$this->playerRow(['team_id' => 1])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FirePlayerController(
			$this->mockI18n(['sell_player_teamsize_too_small' => 'teamsize %s']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('teamsize 5');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionFiresPlayerAndReturnsNull(): void {
		$db = $this->makeDb(['_spieler' => [['number' => 20]]], ['_spieler AS P' => [$this->playerRow(['team_id' => 1])]]);
		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updated) {
			$updated = ['columns' => $columns, 'parameters' => $parameters];
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FirePlayerController(
			$this->mockI18n(['fireplayer_success' => 'fired']), $ws, $db);

		$this->assertNull($controller->executeAction(['id' => 5]));
		$this->assertSame('', $updated['columns']['verein_id']);
		$this->assertSame(0, $updated['columns']['vertrag_spiele']);
		$this->assertSame(5, $updated['parameters']);
	}
}
