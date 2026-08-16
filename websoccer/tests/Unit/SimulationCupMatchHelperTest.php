<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SimulationCupMatchHelper.
 */
final class SimulationCupMatchHelperTest extends TestCaseBase {
	private function makeTeam(int $id, int $goals = 0): SimulationTeam {
		$team = new SimulationTeam($id, 50);
		$team->setGoals($goals);
		return $team;
	}

	private function makeMatch(int $homeId, int $guestId, int $homeGoals, int $guestGoals, string $cupName = 'TestCup', string $cupRound = 'Round1', string $cupGroup = ''): SimulationMatch {
		$home = $this->makeTeam($homeId, $homeGoals);
		$guest = $this->makeTeam($guestId, $guestGoals);
		$match = new SimulationMatch(1, $home, $guest, 90);
		$match->type = 'Pokalspiel';
		$match->cupName = $cupName;
		$match->cupRoundName = $cupRound;
		$match->cupRoundGroup = $cupGroup;
		return $match;
	}

	/**
	 * Creates a DB mock returning the specified row for the first querySelect
	 * call (the "other round" lookup) and empty rows thereafter.
	 */
	private function makeDbWithOtherRound(?array $otherRoundRow): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult($otherRoundRow !== null ? [$otherRoundRow] : []));
		$db->method('queryUpdate')->willReturn(null);
		$db->method('queryInsert')->willReturn(null);
		$db->method('queryDelete')->willReturn(null);
		return $db;
	}

	public function testCheckIfExtensionIsRequiredReturnsFalseForGroupMatch(): void {
		$match = $this->makeMatch(1, 2, 1, 1, 'Cup', 'Round', 'GroupA');
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound(null);

		$this->assertFalse(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsTrueForDrawNoOtherRound(): void {
		$match = $this->makeMatch(1, 2, 1, 1);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound(null);

		$this->assertTrue(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsFalseWhenHomeWinsNoOtherRound(): void {
		$match = $this->makeMatch(1, 2, 3, 0);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound(null);

		$this->assertFalse(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsFalseWhenGuestWinsNoOtherRound(): void {
		$match = $this->makeMatch(1, 2, 0, 3);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound(null);

		$this->assertFalse(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsFalseForFirstRoundNotSimulated(): void {
		$match = $this->makeMatch(1, 2, 2, 1);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		// other round exists but not yet simulated
		$db = $this->makeDbWithOtherRound([
			'home_goals' => 0,
			'guest_goals' => 0,
			'is_simulated' => 0,
		]);

		$this->assertFalse(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsFalseWhenTotalWinnerDecided(): void {
		// This match: home 2, guest 1
		// Other round (home_verein=guest, gast_verein=home): home_goals=0, guest_goals=3
		// Total home = 2 + 0 (other guest_goals) = 2; Total guest = 1 + 3 (other home_goals) = 4
		// Guest wins overall
		$match = $this->makeMatch(1, 2, 2, 1);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound([
			'home_goals' => 3,
			'guest_goals' => 0,
			'is_simulated' => 1,
		]);

		$this->assertFalse(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsTrueWhenTotalDrawAndNoAwayGoalsWinner(): void {
		// This match: home 2, guest 1
		// Other round: home_goals=1, guest_goals=2 (so total home = 2+2=4, total guest = 1+1=2... no)
		// Wait: totalHome = match.home_goals + otherRound.guest_goals = 2 + other.guest_goals
		//       totalGuest = match.guest_goals + otherRound.home_goals = 1 + other.home_goals
		// For a draw: 2 + other.guest_goals == 1 + other.home_goals => other.home_goals - other.guest_goals = 1
		// So other: home_goals=2, guest_goals=1 => total home = 2+1=3, total guest = 1+2=3 => draw
		// Away goals: other.guest_goals (1) vs match.guest_goals (1) => equal => return TRUE
		$match = $this->makeMatch(1, 2, 2, 1);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound([
			'home_goals' => 2,
			'guest_goals' => 1,
			'is_simulated' => 1,
		]);

		$this->assertTrue(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfExtensionIsRequiredReturnsFalseWhenAwayGoalsDecide(): void {
		// This match: home 2, guest 1
		// Other: home_goals=3, guest_goals=2 => total home = 2+2=4, total guest = 1+3=4 => draw
		// Away goals: other.guest_goals (2) > match.guest_goals (1) => home wins
		$match = $this->makeMatch(1, 2, 2, 1);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->makeDbWithOtherRound([
			'home_goals' => 3,
			'guest_goals' => 2,
			'is_simulated' => 1,
		]);

		$this->assertFalse(SimulationCupMatchHelper::checkIfExtensionIsRequired($ws, $db, $match));
	}

	public function testCheckIfMatchIsLastMatchOfGroupRoundReturnsEarlyForNonGroupMatch(): void {
		$match = $this->makeMatch(1, 2, 1, 0, 'Cup', 'Round', '');
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('querySelect');

		SimulationCupMatchHelper::checkIfMatchIsLastMatchOfGroupRoundAndCreateFollowingMatches($ws, $db, $match);
	}
}
