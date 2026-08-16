<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FireYouthPlayerController.
 */
final class FireYouthPlayerControllerTest extends TestCaseBase {
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

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId($clubId);
		return $user;
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FireYouthPlayerController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenPlayerBelongsToAnotherTeam(): void {
		$db = $this->makeDb(['_youthplayer' => [['id' => 5, 'team_id' => 2]]]);
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FireYouthPlayerController(
			$this->mockI18n(['youthteam_err_notownplayer' => 'not own']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not own');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionThrowsWhenPlayerNotFound(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FireYouthPlayerController(
			$this->mockI18n(['error_page_not_found' => 'not found']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not found');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionDeletesOwnYouthPlayerAndReturnsPageId(): void {
		$deleted = false;
		$db = $this->makeDb(['_youthplayer' => [['id' => 5, 'team_id' => 1]]]);
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$ws = $this->mockWebsoccer(['youth_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new FireYouthPlayerController(
			$this->mockI18n(['youthteam_fire_success' => 'fired']), $ws, $db);

		$this->assertSame('youth-team', $controller->executeAction(['id' => 5]));
		$this->assertTrue($deleted);
	}
}
