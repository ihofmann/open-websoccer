<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for MatchReportSimulationObserver.
 */
final class MatchReportSimulationObserverTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position = PLAYER_POSITION_STRIKER): SimulationPlayer {
		$p = new SimulationPlayer($id, $team, $position, 'MS', 3.0, 25, 80, 70, 60, 90, 85);
		$p->name = 'Player' . $id;
		return $p;
	}

	private function makeMatch(): SimulationMatch {
		return new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 10);
	}

	/**
	 * Creates a DB mock whose querySelect returns the supplied text rows on
	 * the first call (constructor) and empty rows thereafter.
	 */
	private function makeDbWithTexts(array $textRows, ?callable $insertCapture = null): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(new MockDbResult($textRows));
		if ($insertCapture !== null) {
			$db->method('queryInsert')->willReturnCallback($insertCapture);
		}
		return $db;
	}

	public function testConstructorLoadsAvailableTexts(): void {
		$db = $this->makeDbWithTexts([
			['id' => 1, 'actiontype' => 'Tor'],
			['id' => 2, 'actiontype' => 'Tor'],
			['id' => 3, 'actiontype' => 'Torschuss_daneben'],
		]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);
		$this->assertInstanceOf(MatchReportSimulationObserver::class, $obs);
	}

	public function testOnGoalCreatesMessageWithAssist(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 5, 'actiontype' => 'Tor_mit_vorlage']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$assister = $this->makePlayer(9, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		$scorer = $this->makePlayer(10, $match->homeTeam);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$match->setPlayerWithBall($assister);
		$match->setPlayerWithBall($scorer);

		$obs->onGoal($match, $scorer, $goaly);
		$this->assertTrue($inserted);
	}

	public function testOnGoalCreatesMessageWithoutAssist(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 5, 'actiontype' => 'Tor']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$scorer = $this->makePlayer(10, $match->homeTeam);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs->onGoal($match, $scorer, $goaly);
		$this->assertTrue($inserted);
	}

	public function testOnShootFailureCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[
				['id' => 5, 'actiontype' => 'Torschuss_daneben'],
				['id' => 6, 'actiontype' => 'Torschuss_auf_Tor'],
			],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$scorer = $this->makePlayer(10, $match->homeTeam);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs->onShootFailure($match, $scorer, $goaly);
		$this->assertTrue($inserted);
	}

	public function testOnAfterTackleCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[
				['id' => 5, 'actiontype' => 'Zweikampf_gewonnen'],
				['id' => 6, 'actiontype' => 'Zweikampf_verloren'],
			],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$winner = $this->makePlayer(10, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		$looser = $this->makePlayer(20, $match->guestTeam, PLAYER_POSITION_MIDFIELD);

		$obs->onAfterTackle($match, $winner, $looser);
		$this->assertTrue($inserted);
	}

	public function testOnBallPassSuccessDoesNothing(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);
		$obs->onBallPassSuccess($this->makeMatch(), $this->makePlayer(1, $this->makeTeam(1)));
		$this->assertFalse($inserted);
	}

	public function testOnYellowCardCreatesMessageForFirstYellow(): void {
		$capturedColumns = null;
		$db = $this->makeDbWithTexts(
			[
				['id' => 5, 'actiontype' => 'Karte_gelb'],
				['id' => 6, 'actiontype' => 'Karte_gelb_rot'],
			],
			function ($columns) use (&$capturedColumns) { $capturedColumns = $columns; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$player = $this->makePlayer(10, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		// first yellow card
		$obs->onYellowCard($match, $player);
		$this->assertNotNull($capturedColumns);
		$this->assertSame(5, $capturedColumns['message_id']);
	}

	public function testOnYellowCardCreatesYellowRedMessageForSecondYellow(): void {
		$capturedColumns = null;
		$db = $this->makeDbWithTexts(
			[
				['id' => 5, 'actiontype' => 'Karte_gelb'],
				['id' => 6, 'actiontype' => 'Karte_gelb_rot'],
			],
			function ($columns) use (&$capturedColumns) { $capturedColumns = $columns; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$player = $this->makePlayer(10, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		// In real flow, DefaultSimulationObserver increments yellowCards before
		// this observer fires, so simulate that state (2 = yellow-red).
		$player->yellowCards = 2;
		// second yellow card
		$obs->onYellowCard($match, $player);
		$this->assertNotNull($capturedColumns);
		$this->assertSame(6, $capturedColumns['message_id']);
	}

	public function testOnRedCardCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 7, 'actiontype' => 'Karte_rot']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$player = $this->makePlayer(10, $match->homeTeam);

		$obs->onRedCard($match, $player, 1);
		$this->assertTrue($inserted);
	}

	public function testOnInjuryCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 7, 'actiontype' => 'Verletzung']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$player = $this->makePlayer(10, $match->homeTeam);

		$obs->onInjury($match, $player, 2);
		$this->assertTrue($inserted);
	}

	public function testOnPenaltyShootSuccessCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[
				['id' => 7, 'actiontype' => 'Elfmeter_erfolg'],
				['id' => 8, 'actiontype' => 'Elfmeter_verschossen'],
			],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$player = $this->makePlayer(10, $match->homeTeam);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs->onPenaltyShoot($match, $player, $goaly, TRUE);
		$this->assertTrue($inserted);
	}

	public function testOnCornerCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[['id' => 7, 'actiontype' => 'Ecke']],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$p1 = $this->makePlayer(10, $match->homeTeam, PLAYER_POSITION_MIDFIELD);
		$p2 = $this->makePlayer(11, $match->homeTeam, PLAYER_POSITION_MIDFIELD);

		$obs->onCorner($match, $p1, $p2);
		$this->assertTrue($inserted);
	}

	public function testOnFreeKickSuccessCreatesMessage(): void {
		$inserted = false;
		$db = $this->makeDbWithTexts(
			[
				['id' => 7, 'actiontype' => 'Freistoss_treffer'],
				['id' => 8, 'actiontype' => 'Freistoss_daneben'],
			],
			function () use (&$inserted) { $inserted = true; }
		);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$obs = new MatchReportSimulationObserver($ws, $db);

		$match = $this->makeMatch();
		$player = $this->makePlayer(10, $match->homeTeam);
		$goaly = $this->makePlayer(1, $match->guestTeam, PLAYER_POSITION_GOALY);

		$obs->onFreeKick($match, $player, $goaly, TRUE);
		$this->assertTrue($inserted);
	}
}
