<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for MoveYouthPlayerToProfessionalController.
 */
final class MoveYouthPlayerToProfessionalControllerTest extends TestCaseBase {
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
		return ['youth_enabled' => true, 'db_prefix' => 'ws', 'youth_min_age_professional' => 17,
			'youth_professionalmove_technique' => 50, 'youth_professionalmove_stamina' => 50,
			'youth_professionalmove_freshness' => 50, 'youth_professionalmove_satisfaction' => 50,
			'youth_salary_per_strength' => 100, 'youth_professionalmove_matches' => 10];
	}

	private function youthPlayerRow(array $override = []): array {
		return array_merge([
			'id' => 5, 'team_id' => 1, 'age' => 18, 'position' => 'Torwart',
			'firstname' => 'A', 'lastname' => 'B', 'nation' => 'Deutschland', 'strength' => 50,
		], $override);
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['youth_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new MoveYouthPlayerToProfessionalController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5, 'mainposition' => 'T']));
	}

	public function testExecuteActionThrowsWhenPlayerIsNotOwn(): void {
		$db = $this->makeDb([], ['_youthplayer' => [$this->youthPlayerRow(['team_id' => 2])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new MoveYouthPlayerToProfessionalController(
			$this->mockI18n(['youthteam_err_notownplayer' => 'not own']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not own');
		$controller->executeAction(['id' => 5, 'mainposition' => 'T']);
	}

	public function testExecuteActionThrowsWhenPlayerTooYoung(): void {
		$db = $this->makeDb([], ['_youthplayer' => [$this->youthPlayerRow(['age' => 15])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new MoveYouthPlayerToProfessionalController(
			$this->mockI18n(['youthteam_makeprofessional_err_tooyoung' => 'too young %s']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('too young 17');
		$controller->executeAction(['id' => 5, 'mainposition' => 'T']);
	}

	public function testExecuteActionThrowsWhenMainPositionInvalid(): void {
		$db = $this->makeDb([], ['_youthplayer' => [$this->youthPlayerRow(['position' => 'Torwart'])]]);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new MoveYouthPlayerToProfessionalController(
			$this->mockI18n(['youthteam_makeprofessional_err_invalidmainposition' => 'invalid position']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid position');
		$controller->executeAction(['id' => 5, 'mainposition' => 'MS']);
	}

	public function testExecuteActionCreatesProfessionalAndReturnsPageId(): void {
		$db = $this->makeDb(
			['_spieler' => [['salary' => 100]]],
			[
				'_youthplayer' => [$this->youthPlayerRow(['position' => 'Torwart'])],
				'_verein AS C' => [['team_id' => 1, 'team_name' => 'My', 'team_budget' => 10000, 'user_id' => 1]],
			]
		);
		$inserted = null;
		$deleted = false;
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserted) {
			$inserted = ['columns' => $columns, 'fromTable' => $fromTable];
			return null;
		});
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new MoveYouthPlayerToProfessionalController(
			$this->mockI18n(['youthteam_makeprofessional_success' => 'promoted']), $ws, $db);

		$this->assertSame('myteam', $controller->executeAction(['id' => 5, 'mainposition' => 'T']));
		$this->assertSame('ws_spieler', $inserted['fromTable']);
		$this->assertSame('T', $inserted['columns']['position_main']);
		$this->assertTrue($deleted);
	}
}
