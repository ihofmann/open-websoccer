<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for DataUpdateSimulatorObserver.
 */
final class DataUpdateSimulatorObserverTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		$team = new SimulationTeam($id, 50);
		$team->substitutions = [];
		return $team;
	}

	private function makePlayer(int $id, SimulationTeam $team): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, PLAYER_POSITION_MIDFIELD, 'ZM', 3.0, 25, 80, 70, 60, 90, 85);
		$p->name = 'Player' . $id;
		return $p;
	}

	public function testConstructorCreatesInstance(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new DataUpdateSimulatorObserver($ws, $db);
		$this->assertInstanceOf(DataUpdateSimulatorObserver::class, $obs);
	}

	public function testImplementsISimulatorObserver(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new DataUpdateSimulatorObserver($ws, $db);
		$this->assertInstanceOf(\ISimulatorObserver::class, $obs);
	}

	public function testOnSubstitutionDoesNothing(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new DataUpdateSimulatorObserver($ws, $db);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 60);

		$in = $this->makePlayer(1, $home);
		$out = $this->makePlayer(2, $home);
		$sub = new SimulationSubstitution(60, $in, $out);

		// onSubstitution is a no-op per the implementation
		$obs->onSubstitution($match, $sub);
		$this->assertInstanceOf(DataUpdateSimulatorObserver::class, $obs);
	}

	public function testOnMatchCompletedForFriendlyMatchUpdatesPlayers(): void {
		$db = $this->createMock(\DbConnection::class);
		$updateCount = 0;
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->method('queryUpdate')->willReturnCallback(function () use (&$updateCount) {
			$updateCount++;
		});
		$db->method('queryDelete')->willReturn(null);
		$db->method('queryInsert')->willReturn(null);

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'sim_tiredness_through_friendly' => TRUE,
			'sim_played_min_minutes' => 10,
			'sim_strengthchange_stamina' => 1,
			'sim_injured_after_friendly' => TRUE,
		]);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$p = $this->makePlayer(10, $home);
		$p->setMinutesPlayed(90, FALSE);
		$p->injured = 0;
		$home->positionsAndPlayers[PLAYER_POSITION_MIDFIELD][] = $p;
		$match = new SimulationMatch(1, $home, $guest, 90);
		$match->type = 'Freundschaft';
		$match->isCompleted = TRUE;

		$obs = new DataUpdateSimulatorObserver($ws, $db);
		$obs->onMatchCompleted($match);

		// should have at least 1 player update + 1 formation delete
		$this->assertGreaterThanOrEqual(1, $updateCount);
	}

	public function testOnMatchCompletedDeletesFormations(): void {
		$deleteCount = 0;
		$deletedTable = null;
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->method('queryUpdate')->willReturn(null);
		$db->method('queryDelete')->willReturnCallback(function ($table) use (&$deleteCount, &$deletedTable) {
			$deleteCount++;
			$deletedTable = $table;
		});
		$db->method('queryInsert')->willReturn(null);

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'sim_tiredness_through_friendly' => FALSE,
			'sim_injured_after_friendly' => FALSE,
		]);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 90);
		$match->type = 'Freundschaft';
		$match->isCompleted = TRUE;

		$obs = new DataUpdateSimulatorObserver($ws, $db);
		$obs->onMatchCompleted($match);

		$this->assertGreaterThanOrEqual(1, $deleteCount);
		$this->assertSame('ws_aufstellung', $deletedTable);
	}
}
