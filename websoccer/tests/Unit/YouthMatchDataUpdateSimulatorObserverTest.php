<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for YouthMatchDataUpdateSimulatorObserver.
 */
final class YouthMatchDataUpdateSimulatorObserverTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		$team = new SimulationTeam($id, 60);
		$team->substitutions = [];
		return $team;
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position = PLAYER_POSITION_MIDFIELD): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, $position, 'ZM', 3.0, 16, 50, 50, 100, 100, 100);
		$p->name = 'Youth' . $id;
		return $p;
	}

	public function testConstructorCreatesInstance(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchDataUpdateSimulatorObserver($ws, $db);
		$this->assertInstanceOf(YouthMatchDataUpdateSimulatorObserver::class, $obs);
	}

	public function testImplementsISimulatorObserver(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchDataUpdateSimulatorObserver($ws, $db);
		$this->assertInstanceOf(\ISimulatorObserver::class, $obs);
	}

	public function testOnBeforeMatchStartsDoesNothing(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchDataUpdateSimulatorObserver($ws, $db);
		$match = new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 0);
		$obs->onBeforeMatchStarts($match);
		$this->assertInstanceOf(YouthMatchDataUpdateSimulatorObserver::class, $obs);
	}

	public function testOnMatchCompletedUpdatesResult(): void {
		$db = $this->mockDb();
		$capturedColumns = null;
		$capturedTable = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $table, $where, $params) use (&$capturedColumns, &$capturedTable) {
			if (strpos($table, 'youthmatch') !== false) {
				$capturedColumns = $columns;
				$capturedTable = $table;
			}
		});

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'sim_played_min_minutes' => 10,
			'youth_scouting_max_strength' => 90,
			'youth_scouting_min_strength' => 10,
		]);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(5, $home, $guest, 90);
		$match->type = YOUTH_MATCH_TYPE;
		$home->setGoals(3);
		$guest->setGoals(1);

		$obs = new YouthMatchDataUpdateSimulatorObserver($ws, $db);
		$obs->onMatchCompleted($match);

		$this->assertNotNull($capturedColumns);
		$this->assertSame(3, $capturedColumns['home_goals']);
		$this->assertSame(1, $capturedColumns['guest_goals']);
		$this->assertSame('1', $capturedColumns['simulated']);
	}

	public function testOnMatchCompletedWithNoFormationSetsFlag(): void {
		$db = $this->mockDb();
		$capturedColumns = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $table) use (&$capturedColumns) {
			if (strpos($table, 'youthmatch') !== false) {
				$capturedColumns = $columns;
			}
		});

		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'sim_played_min_minutes' => 10,
			'youth_scouting_max_strength' => 90,
			'youth_scouting_min_strength' => 10,
		]);

		$home = $this->makeTeam(1);
		$home->noFormationSet = TRUE;
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(5, $home, $guest, 90);
		$match->type = YOUTH_MATCH_TYPE;

		$obs = new YouthMatchDataUpdateSimulatorObserver($ws, $db);
		$obs->onMatchCompleted($match);

		$this->assertSame('1', $capturedColumns['home_noformation']);
		$this->assertSame('0', $capturedColumns['guest_noformation']);
	}

	public function testOnSubstitutionCreatesReportItem(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchDataUpdateSimulatorObserver($ws, $db);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(5, $home, $guest, 60);
		$match->type = YOUTH_MATCH_TYPE;

		$in = $this->makePlayer(1, $home);
		$out = $this->makePlayer(2, $home);
		$sub = new SimulationSubstitution(60, $in, $out);

		// calls YouthMatchesDataService::createMatchReportItem
		// just verify it doesn't throw TypeError
		$obs->onSubstitution($match, $sub);
		$this->assertInstanceOf(YouthMatchDataUpdateSimulatorObserver::class, $obs);
	}
}
