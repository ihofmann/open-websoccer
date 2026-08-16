<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AcceptYouthMatchRequestController.
 */
final class AcceptYouthMatchRequestControllerTest extends TestCaseBase {
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
		return ['youth_enabled' => true, 'youth_matchrequests_enabled' => true, 'db_prefix' => 'ws', 'youth_match_maxperday' => 1];
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => false, 'youth_matchrequests_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptYouthMatchRequestController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionReturnsNullWhenMatchRequestsDisabled(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'youth_matchrequests_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptYouthMatchRequestController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenRequestNotFound(): void {
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptYouthMatchRequestController(
			$this->mockI18n(['youthteam_matchrequest_cancel_err_notfound' => 'not found']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not found');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionThrowsWhenAcceptingOwnRequest(): void {
		$db = $this->makeDb(['_youthmatch_request' => [['id' => 5, 'team_id' => 1, 'matchdate' => 100, 'reward' => 0]]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptYouthMatchRequestController(
			$this->mockI18n(['youthteam_matchrequest_accept_err_ownrequest' => 'own request']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('own request');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionCreatesMatchAndReturnsPageId(): void {
		$db = $this->makeDb(
			[
				'_youthmatch_request' => [['id' => 5, 'team_id' => 2, 'matchdate' => 100, 'reward' => 0]],
				'_youthplayer' => [['hits' => 11]],
				'_youthmatch' => [['hits' => 0]],
			],
			['_verein AS C' => [['team_id' => 1, 'team_name' => 'MyTeam', 'team_budget' => 1000, 'user_id' => 1]]]
		);
		$inserted = [];
		$deleted = false;
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserted) {
			$inserted[] = $fromTable;
			return null;
		});
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new AcceptYouthMatchRequestController($this->mockI18n([
			'youthteam_matchrequest_accept_success' => 'ok',
			'youthteam_matchrequest_accept_success_details' => 'det',
		]), $ws, $db);

		$this->assertSame('youth-matches', $controller->executeAction(['id' => 5]));
		$this->assertContains('ws_youthmatch', $inserted);
		$this->assertTrue($deleted);
	}
}
