<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ChooseSponsorController.
 */
final class ChooseSponsorControllerTest extends TestCaseBase {
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

	public function testExecuteActionReturnsNullWhenUserHasNoClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 3, 'sponsor_matches' => 10]);
		$ws->method('getUser')->willReturn($this->makeUser([]));

		$controller = new ChooseSponsorController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 7]));
	}

	public function testExecuteActionThrowsWhenTeamStillHasSponsorContract(): void {
		$db = $this->makeDb(['_sponsor AS S' => [['sponsor_id' => 1, 'matchdays' => 5]]]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 3, 'sponsor_matches' => 10]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseSponsorController(
			$this->mockI18n(['sponsor_choose_stillcontract' => 'still contract']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('still contract');
		$controller->executeAction(['id' => 7]);
	}

	public function testExecuteActionThrowsWhenMatchdayTooEarly(): void {
		$db = $this->makeDb(['_spiel' => [['matchday' => 1]]]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 3, 'sponsor_matches' => 10]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseSponsorController(
			$this->mockI18n(['sponsor_choose_tooearly' => 'too early %s']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('too early 3');
		$controller->executeAction(['id' => 7]);
	}

	public function testExecuteActionThrowsWhenSponsorNotInOffers(): void {
		$db = $this->makeDb(
			['_spiel' => [['matchday' => 5]], '_verein AS T1' => [['RNK' => 1]]],
			[
				'_verein AS C' => [['team_id' => 1, 'team_name' => 'My', 'team_budget' => 1000, 'user_id' => 1, 'team_league_id' => 1]],
				'_sponsor AS S' => [['sponsor_id' => 7, 'name' => 'OtherSponsor']],
			]
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 3, 'sponsor_matches' => 10]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseSponsorController(
			$this->mockI18n(['sponsor_choose_novalidsponsor' => 'no valid sponsor']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('no valid sponsor');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionAssignsSponsorAndReturnsNull(): void {
		$db = $this->makeDb(
			['_spiel' => [['matchday' => 5]], '_verein AS T1' => [['RNK' => 1]]],
			[
				'_verein AS C' => [['team_id' => 1, 'team_name' => 'My', 'team_budget' => 1000, 'user_id' => 1, 'team_league_id' => 1]],
				'_sponsor AS S' => [['sponsor_id' => 7, 'name' => 'Sponsor', 'amount_match' => 100]],
			]
		);
		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updated) {
			$updated = ['columns' => $columns, 'parameters' => $parameters];
			return null;
		});

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'sponsor_earliest_matchday' => 3, 'sponsor_matches' => 10]);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new ChooseSponsorController(
			$this->mockI18n(['sponsor_choose_success' => 'success']), $ws, $db);

		$this->assertNull($controller->executeAction(['id' => 7]));
		$this->assertSame(7, $updated['columns']['sponsor_id']);
		$this->assertSame(10, $updated['columns']['sponsor_spiele']);
	}
}
