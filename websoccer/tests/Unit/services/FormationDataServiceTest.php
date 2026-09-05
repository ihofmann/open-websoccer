<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for FormationDataService.
 */
final class FormationDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(['db_prefix' => 'ws']);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testGetFormationByTeamIdReturnsRowWhenPresent(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'offensive' => '1', 'setup' => '4-4-2', 'longpasses' => '0', 'counterattacks' => '0',
			 'freekickplayer' => '5', 'player1' => '10', 'player1_pos' => 'T', 'player2' => '11', 'player2_pos' => 'LV',
			 'player3' => '12', 'player3_pos' => 'IV', 'player4' => '13', 'player4_pos' => 'IV', 'player5' => '14', 'player5_pos' => 'RV',
			 'player6' => '15', 'player6_pos' => 'LM', 'player7' => '16', 'player7_pos' => 'ZM', 'player8' => '17', 'player8_pos' => 'ZM',
			 'player9' => '18', 'player9_pos' => 'RM', 'player10' => '19', 'player10_pos' => 'MS', 'player11' => '20', 'player11_pos' => 'LS',
			 'bench1' => '21', 'bench2' => '22', 'bench3' => '23', 'bench4' => '24', 'bench5' => '25',
			 'sub1_out' => '19', 'sub1_in' => '21', 'sub1_minute' => '60', 'sub1_condition' => '0', 'sub1_position' => 'MS',
			 'sub2_out' => '', 'sub2_in' => '', 'sub2_minute' => '', 'sub2_condition' => '', 'sub2_position' => '',
			 'sub3_out' => '', 'sub3_in' => '', 'sub3_minute' => '', 'sub3_condition' => '', 'sub3_position' => ''],
		]));
		$formation = FormationDataService::getFormationByTeamId($ws, $db, 5, 100);
		$this->assertSame('4-4-2', $formation['setup']);
		$this->assertSame('10', $formation['player1']);
	}

	public function testGetFormationByTeamIdReturnsEmptyArrayWhenAbsent(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], FormationDataService::getFormationByTeamId($ws, $db, 5, 100));
	}

	public function testGetFormationByTemplateIdReturnsRowWhenPresent(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['id' => '7', 'offensive' => '0', 'setup' => '4-3-3', 'longpasses' => '1', 'counterattacks' => '1',
			 'freekickplayer' => '3', 'player1' => '1', 'player1_pos' => 'T', 'player2' => '2', 'player2_pos' => 'IV',
			 'player3' => '3', 'player3_pos' => 'IV', 'player4' => '4', 'player4_pos' => 'IV', 'player5' => '5', 'player5_pos' => 'IV',
			 'player6' => '6', 'player6_pos' => 'ZM', 'player7' => '7', 'player7_pos' => 'ZM', 'player8' => '8', 'player8_pos' => 'ZM',
			 'player9' => '9', 'player9_pos' => 'MS', 'player10' => '10', 'player10_pos' => 'LS', 'player11' => '11', 'player11_pos' => 'RS',
			 'bench1' => '12', 'bench2' => '13', 'bench3' => '14', 'bench4' => '15', 'bench5' => '16',
			 'sub1_out' => '', 'sub1_in' => '', 'sub1_minute' => '', 'sub1_condition' => '', 'sub1_position' => '',
			 'sub2_out' => '', 'sub2_in' => '', 'sub2_minute' => '', 'sub2_condition' => '', 'sub2_position' => '',
			 'sub3_out' => '', 'sub3_in' => '', 'sub3_minute' => '', 'sub3_condition' => '', 'sub3_position' => ''],
		]));
		$formation = FormationDataService::getFormationByTemplateId($ws, $db, 5, 7);
		$this->assertSame('7', $formation['id']);
		$this->assertSame('4-3-3', $formation['setup']);
	}

	public function testGetFormationByTemplateIdReturnsEmptyArrayWhenAbsent(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], FormationDataService::getFormationByTemplateId($ws, $db, 5, 999));
	}

	private function playerRow($id, $position, $main, $second = ''): array {
		return ['id' => (string) $id, 'position' => $position, 'position_main' => $main, 'position_second' => $second];
	}

	public function testGetFormationProposalFillsAllOpenPositionsByMainPosition(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->playerRow(1, 'Torwart', 'T'),
			$this->playerRow(2, 'Abwehr', 'LV'),
			$this->playerRow(3, 'Abwehr', 'RV'),
			$this->playerRow(4, 'Abwehr', 'IV'),
			$this->playerRow(5, 'Abwehr', 'IV'),
			$this->playerRow(6, 'Mittelfeld', 'LM'),
			$this->playerRow(7, 'Mittelfeld', 'ZM'),
			$this->playerRow(8, 'Mittelfeld', 'ZM'),
			$this->playerRow(9, 'Mittelfeld', 'RM'),
			$this->playerRow(10, 'Sturm', 'MS'),
			$this->playerRow(11, 'Sturm', 'LS'),
			$this->playerRow(12, 'Sturm', 'RS'),
		]));
		// defense=4, midfield=4, striker=1, outsideforward=2, keeper=1 -> 12 positions.
		$players = FormationDataService::getFormationProposalForTeamId($ws, $db, 5, 4, 0, 4, 0, 1, 2, 'w_staerke', 'DESC');
		$this->assertCount(12, $players);
		$positions = array_column($players, 'position');
		$this->assertContains('T', $positions);
		$this->assertContains('LS', $positions);
		$this->assertContains('RS', $positions);
		// two IV and two ZM filled.
		$this->assertCount(2, array_filter($positions, fn($p) => $p === 'IV'));
		$this->assertCount(2, array_filter($positions, fn($p) => $p === 'ZM'));
	}

	public function testGetFormationProposalReturnsEmptyWhenNoPlayers(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$players = FormationDataService::getFormationProposalForTeamId($ws, $db, 5, 4, 0, 3, 0, 1, 0, 'w_staerke', 'DESC');
		$this->assertSame([], $players);
	}

	public function testGetFormationProposalFillsRemainingOpenSlotsFromSecondaryPosition(): void {
		// A player whose main position is already full but who has a matching
		// secondary position must be used to fill the remaining open slot.
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->playerRow(1, 'Torwart', 'T'),
			$this->playerRow(2, 'Abwehr', 'IV'),
			$this->playerRow(3, 'Abwehr', 'LV', 'IV'),
		]));
		// defense=3 -> IV=3, LV=0, RV=0.
		$players = FormationDataService::getFormationProposalForTeamId($ws, $db, 5, 3, 0, 0, 0, 0, 0, 'w_staerke', 'DESC');
		$ids = array_column($players, 'id');
		$this->assertContains('1', $ids);
		// player2 fills an IV slot via its main position.
		$this->assertContains('2', $ids);
		// player3's main position (LV) is closed, but its secondary position
		// (IV) is still open, so it must be picked up by the secondary loop.
		$this->assertContains('3', $ids);
		$player3 = array_values(array_filter($players, fn($p) => $p['id'] === '3'))[0];
		$this->assertSame('IV', $player3['position']);
	}

	public function testGetFormationProposalDropsPlayerWithoutMatchingSecondaryPosition(): void {
		// A player whose main position is full and who has no secondary position
		// cannot fill any slot and is simply dropped.
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->playerRow(1, 'Torwart', 'T'),
			$this->playerRow(2, 'Torwart', 'T'),
			$this->playerRow(3, 'Abwehr', 'IV'),
		]));
		// defense=3 -> IV=3, LV=0, RV=0.
		$players = FormationDataService::getFormationProposalForTeamId($ws, $db, 5, 3, 0, 0, 0, 0, 0, 'w_staerke', 'DESC');
		$ids = array_column($players, 'id');
		$this->assertContains('1', $ids);
		$this->assertContains('3', $ids);
		$this->assertNotContains('2', $ids);
	}

	public function testGetFormationProposalHandlesNullPositionFields(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			$this->playerRow(1, 'Torwart', null, null),
			$this->playerRow(2, 'Abwehr', 'IV', null),
		]));

		$players = FormationDataService::getFormationProposalForTeamId($ws, $db, 5, 0, 0, 0, 0, 0, 0, 'w_staerke', 'DESC');

		$this->assertSame([
			['id' => '1', 'position' => 'T'],
		], $players);
	}
}
