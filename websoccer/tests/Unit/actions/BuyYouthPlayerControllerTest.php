<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for BuyYouthPlayerController.
 */
final class BuyYouthPlayerControllerTest extends TestCaseBase {
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

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BuyYouthPlayerController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenUserHasNoTeam(): void {
		$user = $this->makeUser(['id' => 1]); // no club
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new BuyYouthPlayerController(
			$this->mockI18n(['feature_requires_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionThrowsWhenPlayerIsOwn(): void {
		$db = $this->makeDb([], ['_youthplayer' => [['id' => 5, 'team_id' => 1, 'transfer_fee' => 100]]]);
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BuyYouthPlayerController(
			$this->mockI18n(['youthteam_buy_err_ownplayer' => 'own player']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('own player');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionThrowsWhenBudgetNotEnough(): void {
		$db = $this->makeDb(
			['_verein' => [['user_id' => 99]]],
			[
				'_youthplayer' => [['id' => 5, 'team_id' => 2, 'transfer_fee' => 500, 'firstname' => 'A', 'lastname' => 'B']],
				'_verein AS C' => [['team_id' => 1, 'team_name' => 'MyTeam', 'team_budget' => 100, 'user_id' => 1]],
			]
		);
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new BuyYouthPlayerController(
			$this->mockI18n(['youthteam_buy_err_notenoughbudget' => 'not enough budget']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not enough budget');
		$controller->executeAction(['id' => 5]);
	}
}
