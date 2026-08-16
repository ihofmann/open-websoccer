<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ChooseTeamController.
 */
final class ChooseTeamControllerTest extends TestCaseBase {
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
		return $db;
	}

	public function testExecuteActionThrowsWhenFeatureDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['assign_team_automatically' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseTeamController(
			$this->mockI18n(['freeclubs_msg_error' => 'disabled']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('disabled');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionThrowsWhenUserAlreadyManagesClub(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(3);

		$ws = $this->mockWebsoccer(['assign_team_automatically' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseTeamController(
			$this->mockI18n(['freeclubs_msg_error_user_is_already_manager' => 'already manager']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('already manager');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionThrowsWhenClubHasManager(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(0);

		$db = $this->makeDb(['_verein' => [['id' => null]]]);
		$ws = $this->mockWebsoccer(['assign_team_automatically' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseTeamController(
			$this->mockI18n(['freeclubs_msg_error' => 'no free club']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('no free club');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionAssignsTeamAndReturnsOffice(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(0);

		$updated = false;
		$db = $this->makeDb(['_verein' => [['id' => 5]]]);
		$db->method('queryUpdate')->willReturnCallback(function () use (&$updated) {
			$updated = true;
			return null;
		});

		$ws = $this->mockWebsoccer(['assign_team_automatically' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseTeamController(
			$this->mockI18n(['freeclubs_msg_success' => 'success']), $ws, $db);

		$this->assertSame('office', $controller->executeAction(['teamId' => 5]));
		$this->assertTrue($updated);
	}
}
