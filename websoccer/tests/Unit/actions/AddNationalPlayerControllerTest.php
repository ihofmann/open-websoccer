<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AddNationalPlayerController.
 */
final class AddNationalPlayerControllerTest extends TestCaseBase {
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

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new AddNationalPlayerController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenUserDoesNotManageNationalTeam(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new AddNationalPlayerController(
			$this->mockI18n(['nationalteams_user_requires_team' => 'requires team']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionThrowsWhenPlayerNotFound(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb(
			['_verein' => [['name' => 'Deutschland']], '_spieler' => []],
			['_verein' => [['id' => 5]]]
		);
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new AddNationalPlayerController(
			$this->mockI18n(['error_page_not_found' => 'not found']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not found');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionThrowsWhenPlayerIsFromDifferentNation(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb(
			['_verein' => [['name' => 'Deutschland']], '_spieler' => [['nation' => 'Brasilien']]],
			['_verein' => [['id' => 5]]]
		);
		$ws = $this->mockWebsoccer(['nationalteams_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new AddNationalPlayerController($this->mockI18n(), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Player is from different nation.');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionAddsPlayerAndReturnsPageId(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb(
			[
				'_verein' => [['name' => 'Deutschland']],
				'_spieler' => [['nation' => 'Deutschland']],
				'_nationalplayer' => [['hits' => 0]],
			],
			['_verein' => [['id' => 5]]]
		);
		$inserted = null;
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserted) {
			$inserted = ['columns' => $columns, 'fromTable' => $fromTable];
			return null;
		});

		$ws = $this->mockWebsoccer(['nationalteams_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new AddNationalPlayerController(
			$this->mockI18n(['nationalteams_addplayer_success' => 'added']), $ws, $db);

		$this->assertSame('nationalteam', $controller->executeAction(['id' => 99]));
		$this->assertSame(5, $inserted['columns']['team_id']);
		$this->assertSame(99, $inserted['columns']['player_id']);
		$this->assertSame('ws_nationalplayer', $inserted['fromTable']);
	}
}
