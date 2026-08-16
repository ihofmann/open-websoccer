<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CancelYouthMatchRequestController.
 */
final class CancelYouthMatchRequestControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
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
		$db->method('queryCachedSelect')->willReturn([]);
		return $db;
	}

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId($clubId);
		return $user;
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelYouthMatchRequestController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenRequestNotFound(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelYouthMatchRequestController(
			$this->mockI18n(['youthteam_matchrequest_cancel_err_notfound' => 'not found']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not found');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionThrowsWhenRequestBelongsToAnotherTeam(): void {
		$db = $this->makeDb(['_youthmatch_request' => [['id' => 5, 'team_id' => 2]]]);
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelYouthMatchRequestController($this->mockI18n(), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('nice try');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionDeletesOwnRequestAndReturnsPageId(): void {
		$deleted = false;
		$db = $this->makeDb(['_youthmatch_request' => [['id' => 5, 'team_id' => 1]]]);
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CancelYouthMatchRequestController(
			$this->mockI18n(['youthteam_matchrequest_cancel_success' => 'cancelled']), $ws, $db);

		$this->assertSame('youth-matchrequests', $controller->executeAction(['id' => 5]));
		$this->assertTrue($deleted);
	}
}
