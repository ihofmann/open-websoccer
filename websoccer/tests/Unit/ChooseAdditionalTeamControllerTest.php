<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ChooseAdditionalTeamController.
 */
final class ChooseAdditionalTeamControllerTest extends TestCaseBase {
	/** Builds a DbConnection mock whose querySelect dispatches on the WHERE clause. */
	private function makeDb(array $whereToRows = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($whereToRows) {
				foreach ($whereToRows as $needle => $rows) {
					if (strpos($whereCondition, $needle) !== false) {
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
		$ws = $this->mockWebsoccer(['assign_team_automatically' => false, 'max_number_teams_per_user' => 2, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseAdditionalTeamController(
			$this->mockI18n(['freeclubs_msg_error' => 'disabled']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('disabled');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionThrowsWhenMaxNumberOfTeamsReached(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb(['user_id = %d' => [['id' => 10, 'liga_id' => 1]]]);
		$ws = $this->mockWebsoccer([
			'assign_team_automatically' => true, 'max_number_teams_per_user' => 1,
			'additional_team_min_highscore' => 0, 'db_prefix' => 'ws',
		]);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseAdditionalTeamController(
			$this->mockI18n(['freeclubs_msg_error_max_number_of_teams' => 'max teams %s']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('max teams 1');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionThrowsWhenClubAlreadyHasManager(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb([
			'user_id = %d' => [['id' => 10, 'liga_id' => 1]],
			'id = %d AND status = 1' => [['id' => 5, 'user_id' => 9, 'liga_id' => 2, 'interimmanager' => 0]],
		]);
		$ws = $this->mockWebsoccer([
			'assign_team_automatically' => true, 'max_number_teams_per_user' => 2,
			'additional_team_min_highscore' => 0, 'db_prefix' => 'ws',
		]);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseAdditionalTeamController(
			$this->mockI18n(['freeclubs_msg_error' => 'has manager']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('has manager');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionThrowsWhenClubFromSameLeague(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb([
			'user_id = %d' => [['id' => 10, 'liga_id' => 2]],
			'id = %d AND status = 1' => [['id' => 5, 'user_id' => 0, 'liga_id' => 2, 'interimmanager' => 0]],
		]);
		$ws = $this->mockWebsoccer([
			'assign_team_automatically' => true, 'max_number_teams_per_user' => 2,
			'additional_team_min_highscore' => 0, 'db_prefix' => 'ws',
		]);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseAdditionalTeamController(
			$this->mockI18n(['freeclubs_msg_error_no_club_from_same_league' => 'same league']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('same league');
		$controller->executeAction(['teamId' => 5]);
	}

	public function testExecuteActionAssignsAdditionalTeamAndReturnsOffice(): void {
		$user = $this->makeUser(['id' => 1]);
		$db = $this->makeDb([
			'user_id = %d' => [['id' => 10, 'liga_id' => 1]],
			'id = %d AND status = 1' => [['id' => 5, 'user_id' => 0, 'liga_id' => 2, 'interimmanager' => 0]],
		]);
		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updated) {
			$updated = ['columns' => $columns, 'parameters' => $parameters];
			return null;
		});

		$ws = $this->mockWebsoccer([
			'assign_team_automatically' => true, 'max_number_teams_per_user' => 2,
			'additional_team_min_highscore' => 0, 'db_prefix' => 'ws',
		]);
		$ws->method('getUser')->willReturn($user);

		$controller = new ChooseAdditionalTeamController(
			$this->mockI18n(['freeclubs_msg_success' => 'success']), $ws, $db);

		$this->assertSame('office', $controller->executeAction(['teamId' => 5]));
		$this->assertSame(1, $updated['columns']['user_id']);
		$this->assertSame(5, $updated['parameters']);
		$this->assertSame(5, $user->getClubId());
	}
}
