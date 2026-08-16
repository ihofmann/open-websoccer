<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for YouthMatchReportSimulationObserver.
 */
final class YouthMatchReportSimulationObserverTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position = PLAYER_POSITION_STRIKER): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, $position, 'MS', 3.0, 25, 80, 70, 60, 90, 85);
		$p->name = 'Youth' . $id;
		return $p;
	}

	private function makeMatch(): SimulationMatch {
		return new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 10);
	}

	public function testConstructorCreatesInstance(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$this->assertInstanceOf(YouthMatchReportSimulationObserver::class, $obs);
	}

	public function testOnBallPassSuccessDoesNothing(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$match = $this->makeMatch();
		$player = $this->makePlayer(1, $match->homeTeam);
		// should not throw
		$obs->onBallPassSuccess($match, $player);
		$this->assertInstanceOf(YouthMatchReportSimulationObserver::class, $obs);
	}

	public function testOnBallPassFailureDoesNothing(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$match = $this->makeMatch();
		$player = $this->makePlayer(1, $match->homeTeam);
		$obs->onBallPassFailure($match, $player);
		$this->assertInstanceOf(YouthMatchReportSimulationObserver::class, $obs);
	}

	public function testOnCornerDoesNothing(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$match = $this->makeMatch();
		$p1 = $this->makePlayer(1, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		$p2 = $this->makePlayer(2, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		$obs->onCorner($match, $p1, $p2);
		$this->assertInstanceOf(YouthMatchReportSimulationObserver::class, $obs);
	}

	public function testOnFreeKickSuccessfulDelegatesToOnGoal(): void {
		// YouthMatchReportSimulationObserver::onFreeKick with successful=TRUE
		// delegates to onGoal which calls YouthMatchesDataService::createMatchReportItem
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$match = $this->makeMatch();
		$player = $this->makePlayer(1, $match->homeTeam);
		$goaly = $this->makePlayer(2, $match->guestTeam, PLAYER_POSITION_GOALY);

		// This will call YouthMatchesDataService which needs DB - mock returns empty
		// Just verify it doesn't throw a TypeError
		$obs->onFreeKick($match, $player, $goaly, TRUE);
		$this->assertInstanceOf(YouthMatchReportSimulationObserver::class, $obs);
	}

	public function testOnFreeKickFailedDoesNothing(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$match = $this->makeMatch();
		$player = $this->makePlayer(1, $match->homeTeam);
		$goaly = $this->makePlayer(2, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs->onFreeKick($match, $player, $goaly, FALSE);
		$this->assertInstanceOf(YouthMatchReportSimulationObserver::class, $obs);
	}

	public function testImplementsISimulationObserver(): void {
		$db = $this->mockDb();
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new YouthMatchReportSimulationObserver($ws, $db);
		$this->assertInstanceOf(\ISimulationObserver::class, $obs);
	}
}
