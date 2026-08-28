<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for SimulationFormationHelper.
 */
final class SimulationFormationHelperTest extends TestCaseBase {
	private function makeTeam(int $id, bool $isNational = false): SimulationTeam {
		$team = new SimulationTeam($id, 60);
		$team->isNationalTeam = $isNational;
		return $team;
	}

	/**
	 * Builds a DB mock that returns the specified player rows from querySelect.
	 */
	private function makeDbWithPlayers(array $playerRows): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult($playerRows));
		$db->method('queryInsert')->willReturn(null);
		return $db;
	}

	public function testGenerateNewFormationForTeamFillsAllPositions(): void {
		$playerRows = [];
		$positions = [PLAYER_POSITION_GOALY, PLAYER_POSITION_DEFENCE, PLAYER_POSITION_DEFENCE,
			PLAYER_POSITION_DEFENCE, PLAYER_POSITION_DEFENCE,
			PLAYER_POSITION_MIDFIELD, PLAYER_POSITION_MIDFIELD, PLAYER_POSITION_MIDFIELD, PLAYER_POSITION_MIDFIELD,
			PLAYER_POSITION_STRIKER, PLAYER_POSITION_STRIKER];
		$mainPositions = ['T', 'LV', 'IV', 'IV', 'RV', 'LM', 'ZM', 'ZM', 'RM', 'LS', 'RS'];

		for ($i = 0; $i < 11; $i++) {
			$playerRows[] = [
				'id' => $i + 1,
				'position' => $positions[$i],
				'mainPosition' => $mainPositions[$i],
				'firstName' => 'First' . ($i + 1),
				'lastName' => 'Last' . ($i + 1),
				'pseudonym' => '',
				'strength' => 80,
				'technique' => 70,
				'stamina' => 60,
				'freshness' => 90,
				'satisfaction' => 85,
				'age' => 25,
			];
		}

		$db = $this->makeDbWithPlayers($playerRows);
		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'players_aging' => 'season',
		]);

		$team = $this->makeTeam(1);
		$match = new SimulationMatch(1, $team, $this->makeTeam(2), 0);

		SimulationFormationHelper::generateNewFormationForTeam($ws, $db, $team, 1);

		$this->assertSame(1, count($team->positionsAndPlayers[PLAYER_POSITION_GOALY]));
		$this->assertSame(4, count($team->positionsAndPlayers[PLAYER_POSITION_DEFENCE]));
		$this->assertSame(4, count($team->positionsAndPlayers[PLAYER_POSITION_MIDFIELD]));
		$this->assertSame(2, count($team->positionsAndPlayers[PLAYER_POSITION_STRIKER]));
	}

	public function testGenerateNewFormationSetsPlayerNames(): void {
		$playerRows = [
			[
				'id' => 1, 'position' => PLAYER_POSITION_GOALY, 'mainPosition' => 'T',
				'firstName' => 'John', 'lastName' => 'Doe', 'pseudonym' => '',
				'strength' => 80, 'technique' => 70, 'stamina' => 60,
				'freshness' => 90, 'satisfaction' => 85, 'age' => 25,
			],
		];

		$db = $this->makeDbWithPlayers($playerRows);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'players_aging' => 'season']);

		$team = $this->makeTeam(1);
		$match = new SimulationMatch(1, $team, $this->makeTeam(2), 0);

		SimulationFormationHelper::generateNewFormationForTeam($ws, $db, $team, 1);

		$goaly = $team->positionsAndPlayers[PLAYER_POSITION_GOALY][0];
		$this->assertSame('John Doe', $goaly->name);
	}

	public function testGenerateNewFormationFallsBackToFullNameForNullPseudonym(): void {
		$playerRows = [
			[
				'id' => 1, 'position' => PLAYER_POSITION_GOALY, 'mainPosition' => 'T',
				'firstName' => 'John', 'lastName' => 'Doe', 'pseudonym' => null,
				'strength' => 80, 'technique' => 70, 'stamina' => 60,
				'freshness' => 90, 'satisfaction' => 85, 'age' => 25,
			],
		];

		$db = $this->makeDbWithPlayers($playerRows);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'players_aging' => 'season']);

		$team = $this->makeTeam(1);
		$match = new SimulationMatch(1, $team, $this->makeTeam(2), 0);

		SimulationFormationHelper::generateNewFormationForTeam($ws, $db, $team, 1);

		$goaly = $team->positionsAndPlayers[PLAYER_POSITION_GOALY][0];
		$this->assertSame('John Doe', $goaly->name);
	}

	public function testGenerateNewFormationUsesPseudonymWhenAvailable(): void {
		$playerRows = [
			[
				'id' => 1, 'position' => PLAYER_POSITION_GOALY, 'mainPosition' => 'T',
				'firstName' => 'John', 'lastName' => 'Doe', 'pseudonym' => 'SuperGoalie',
				'strength' => 80, 'technique' => 70, 'stamina' => 60,
				'freshness' => 90, 'satisfaction' => 85, 'age' => 25,
			],
		];

		$db = $this->makeDbWithPlayers($playerRows);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'players_aging' => 'season']);

		$team = $this->makeTeam(1);
		$match = new SimulationMatch(1, $team, $this->makeTeam(2), 0);

		SimulationFormationHelper::generateNewFormationForTeam($ws, $db, $team, 1);

		$goaly = $team->positionsAndPlayers[PLAYER_POSITION_GOALY][0];
		$this->assertSame('SuperGoalie', $goaly->name);
	}

	public function testGenerateNewFormationPreventsDuplicateLV(): void {
		// Two left-backs: second should become IV
		$playerRows = [
			[
				'id' => 1, 'position' => PLAYER_POSITION_GOALY, 'mainPosition' => 'T',
				'firstName' => 'G', 'lastName' => 'K', 'pseudonym' => '',
				'strength' => 80, 'technique' => 70, 'stamina' => 60,
				'freshness' => 90, 'satisfaction' => 85, 'age' => 25,
			],
			[
				'id' => 2, 'position' => PLAYER_POSITION_DEFENCE, 'mainPosition' => 'LV',
				'firstName' => 'L', 'lastName' => 'B1', 'pseudonym' => '',
				'strength' => 80, 'technique' => 70, 'stamina' => 60,
				'freshness' => 90, 'satisfaction' => 85, 'age' => 25,
			],
			[
				'id' => 3, 'position' => PLAYER_POSITION_DEFENCE, 'mainPosition' => 'LV',
				'firstName' => 'L', 'lastName' => 'B2', 'pseudonym' => '',
				'strength' => 80, 'technique' => 70, 'stamina' => 60,
				'freshness' => 90, 'satisfaction' => 85, 'age' => 25,
			],
		];

		$db = $this->makeDbWithPlayers($playerRows);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'players_aging' => 'season']);

		$team = $this->makeTeam(1);
		$match = new SimulationMatch(1, $team, $this->makeTeam(2), 0);

		SimulationFormationHelper::generateNewFormationForTeam($ws, $db, $team, 1);

		$defenders = $team->positionsAndPlayers[PLAYER_POSITION_DEFENCE];
		$this->assertSame(2, count($defenders));
		$this->assertSame('LV', $defenders[0]->mainPosition);
		$this->assertSame('IV', $defenders[1]->mainPosition);
	}
}
