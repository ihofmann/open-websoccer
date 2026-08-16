<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LendPlayerController.
 */
final class LendPlayerControllerTest extends TestCaseBase {
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
		return ['lending_enabled' => true, 'db_prefix' => 'ws',
			'transfermarket_min_teamsize' => 11, 'lending_matches_min' => 1,
			'players_aging' => 'age', 'transfermarket_computed_marketvalue' => false];
	}

	private function playerRow(array $override = []): array {
		return array_merge([
			'player_id' => 5, 'team_id' => 1, 'lending_owner_id' => 0, 'lending_fee' => 0,
			'player_transfermarket' => 0, 'player_contract_matches' => 50,
			'player_marketvalue' => 1000, 'player_position' => 'Torwart',
			'player_nationality' => 'Deutschland', 'matches_info' => '0;0',
		], $override);
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['lending_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new LendPlayerController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5, 'fee' => 100]));
	}

	public function testExecuteActionThrowsWhenPlayerIsNotOwn(): void {
		$db = $this->makeDb([], ['_spieler AS P' => [$this->playerRow(['team_id' => 2])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new LendPlayerController(
			$this->mockI18n(['lending_err_notownplayer' => 'not own']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not own');
		$controller->executeAction(['id' => 5, 'fee' => 100]);
	}

	public function testExecuteActionThrowsWhenPlayerAlreadyOfferedForLending(): void {
		$db = $this->makeDb([], ['_spieler AS P' => [$this->playerRow(['lending_fee' => 100])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new LendPlayerController(
			$this->mockI18n(['lending_err_alreadyoffered' => 'already offered']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('already offered');
		$controller->executeAction(['id' => 5, 'fee' => 100]);
	}

	public function testExecuteActionThrowsWhenTeamSizeTooSmall(): void {
		$db = $this->makeDb(['_spieler' => [['number' => 5]]], ['_spieler AS P' => [$this->playerRow()]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new LendPlayerController(
			$this->mockI18n(['lending_err_teamsize_too_small' => 'teamsize %s']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('teamsize 5');
		$controller->executeAction(['id' => 5, 'fee' => 100]);
	}

	public function testExecuteActionLendsPlayerAndReturnsPageId(): void {
		$db = $this->makeDb(['_spieler' => [['number' => 20]]], ['_spieler AS P' => [$this->playerRow()]]);
		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updated) {
			$updated = ['columns' => $columns, 'parameters' => $parameters];
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new LendPlayerController(
			$this->mockI18n(['lend_player_success' => 'lent']), $ws, $db);

		$this->assertSame('myteam', $controller->executeAction(['id' => 5, 'fee' => 100]));
		$this->assertSame(100, $updated['columns']['lending_fee']);
		$this->assertSame(5, $updated['parameters']);
	}
}
