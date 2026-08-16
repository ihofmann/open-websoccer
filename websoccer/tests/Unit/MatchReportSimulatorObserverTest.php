<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for MatchReportSimulatorObserver.
 */
final class MatchReportSimulatorObserverTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position = PLAYER_POSITION_MIDFIELD): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, $position, 'ZM', 3.0, 25, 80, 70, 60, 90, 85);
		$p->name = 'Player' . $id;
		return $p;
	}

	private function makeDbWithTexts(array $textRows, ?callable $insertCapture = null): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult($textRows));
		if ($insertCapture !== null) {
			$db->method('queryInsert')->willReturnCallback($insertCapture);
		}
		return $db;
	}

	public function testConstructorLoadsSubstitutionTexts(): void {
		$db = $this->makeDbWithTexts([['id' => 1, 'actiontype' => 'Auswechslung']]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulatorObserver($ws, $db);
		$this->assertInstanceOf(MatchReportSimulatorObserver::class, $obs);
	}

	public function testOnSubstitutionCreatesMessage(): void {
		$inserted = false;
		$capturedColumns = null;
		$db = $this->makeDbWithTexts(
			[['id' => 10, 'actiontype' => 'Auswechslung']],
			function ($columns) use (&$inserted, &$capturedColumns) {
				$inserted = true;
				$capturedColumns = $columns;
			}
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulatorObserver($ws, $db);

		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(5, $home, $guest, 60);

		$in = $this->makePlayer(1, $home);
		$out = $this->makePlayer(2, $home);
		$sub = new SimulationSubstitution(60, $in, $out);

		$obs->onSubstitution($match, $sub);
		$this->assertTrue($inserted);
		$this->assertSame(5, $capturedColumns['match_id']);
		$this->assertSame(60, $capturedColumns['minute']);
	}

	public function testOnMatchCompletedDoesNothing(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 1, 'actiontype' => 'Auswechslung']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulatorObserver($ws, $db);

		$match = new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 90);
		$obs->onMatchCompleted($match);
		$this->assertFalse($inserted);
	}

	public function testOnBeforeMatchStartsDoesNothing(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 1, 'actiontype' => 'Auswechslung']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulatorObserver($ws, $db);

		$match = new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 0);
		$obs->onBeforeMatchStarts($match);
		$this->assertFalse($inserted);
	}
}
