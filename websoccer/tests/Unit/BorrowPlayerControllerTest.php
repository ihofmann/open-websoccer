<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for BorrowPlayerController.
 */
final class BorrowPlayerControllerTest extends TestCaseBase {
	private function makeDb(array $cachedRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
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
		return ['lending_enabled' => true, 'db_prefix' => 'ws', 'players_aging' => 'age',
			'transfermarket_computed_marketvalue' => false,
			'lending_matches_min' => 1, 'lending_matches_max' => 50];
	}

	private function playerRow(array $override = []): array {
		return array_merge([
			'player_id' => 5, 'team_id' => 2, 'team_name' => 'Other', 'team_user_id' => 2,
			'lending_owner_id' => 0, 'lending_fee' => 100, 'lending_matches' => 0,
			'player_transfermarket' => 0, 'player_contract_matches' => 50, 'player_contract_salary' => 10,
			'player_pseudonym' => '', 'player_firstname' => 'A', 'player_lastname' => 'B',
			'player_position' => 'Torwart', 'player_nationality' => 'Deutschland',
			'matches_info' => '0;0', 'player_marketvalue' => 1000,
		], $override);
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['lending_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BorrowPlayerController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5, 'matches' => 5]));
	}

	public function testExecuteActionThrowsWhenUserHasNoTeam(): void {
		$user = $this->makeUser(['id' => 1]); // no club set -> getClubId returns null
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($user);

		$controller = new BorrowPlayerController(
			$this->mockI18n(['feature_requires_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['id' => 5, 'matches' => 5]);
	}

	public function testExecuteActionThrowsWhenPlayerIsOwn(): void {
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['team_id' => 1])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BorrowPlayerController(
			$this->mockI18n(['lending_hire_err_ownplayer' => 'own player']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('own player');
		$controller->executeAction(['id' => 5, 'matches' => 5]);
	}

	public function testExecuteActionThrowsWhenPlayerNotOfferedForLending(): void {
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow(['lending_fee' => 0])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BorrowPlayerController(
			$this->mockI18n(['lending_hire_err_notoffered' => 'not offered']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not offered');
		$controller->executeAction(['id' => 5, 'matches' => 5]);
	}

	public function testExecuteActionThrowsWhenDurationOutOfRange(): void {
		$db = $this->makeDb(['_spieler AS P' => [$this->playerRow()]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BorrowPlayerController($this->mockI18n([
			'lending_hire_err_illegalduration' => 'Illegal duration: %s, %s',
		]), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Illegal duration: 1, 50');
		$controller->executeAction(['id' => 5, 'matches' => 999]);
	}
}
