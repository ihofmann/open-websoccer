<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SimulationMatch.
 */
final class SimulationMatchTest extends TestCaseBase {
	private function makeTeam(int $id): SimulationTeam {
		return new SimulationTeam($id, 50);
	}

	private function makePlayer(int $id, SimulationTeam $team, string $position = PLAYER_POSITION_DEFENCE): SimulationPlayer {
		return new SimulationPlayer($id, $team, $position, 'IV', 3.0, 25, 80, 70, 60, 90, 85);
	}

	public function testConstructorSetsBasicProperties(): void {
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(99, $home, $guest, 5);

		$this->assertSame(99, $match->id);
		$this->assertSame($home, $match->homeTeam);
		$this->assertSame($guest, $match->guestTeam);
		$this->assertSame(5, $match->minute);
	}

	public function testConstructorSetsDefaultFlags(): void {
		$match = new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 0);
		$this->assertFalse($match->isCompleted);
		$this->assertFalse($match->penaltyShootingEnabled);
		$this->assertFalse($match->isSoldOut);
	}

	public function testConstructorWithPlayerWithBall(): void {
		$home = $this->makeTeam(1);
		$p = $this->makePlayer(10, $home);
		$match = new SimulationMatch(1, $home, $this->makeTeam(2), 1, $p);
		$this->assertSame($p, $match->getPlayerWithBall());
	}

	public function testGetPlayerWithBallReturnsNullByDefault(): void {
		$match = new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 0);
		$this->assertNull($match->getPlayerWithBall());
	}

	public function testGetPreviousPlayerWithBallReturnsNullByDefault(): void {
		$match = new SimulationMatch(1, $this->makeTeam(1), $this->makeTeam(2), 0);
		$this->assertNull($match->getPreviousPlayerWithBall());
	}

	public function testSetPlayerWithBallSetsCurrentPlayer(): void {
		$home = $this->makeTeam(1);
		$p = $this->makePlayer(10, $home);
		$match = new SimulationMatch(1, $home, $this->makeTeam(2), 1);
		$match->setPlayerWithBall($p);
		$this->assertSame($p, $match->getPlayerWithBall());
	}

	public function testSetPlayerWithBallMovesPreviousToPreviousSlot(): void {
		$home = $this->makeTeam(1);
		$p1 = $this->makePlayer(10, $home);
		$p2 = $this->makePlayer(11, $home);
		$match = new SimulationMatch(1, $home, $this->makeTeam(2), 1);
		$match->setPlayerWithBall($p1);
		$match->setPlayerWithBall($p2);

		$this->assertSame($p2, $match->getPlayerWithBall());
		$this->assertSame($p1, $match->getPreviousPlayerWithBall());
	}

	public function testSetPlayerWithBallIncrementsBallContacts(): void {
		$home = $this->makeTeam(1);
		$p1 = $this->makePlayer(10, $home);
		$p2 = $this->makePlayer(11, $home);
		$match = new SimulationMatch(1, $home, $this->makeTeam(2), 1);
		$match->setPlayerWithBall($p1);
		$this->assertSame(0, $p2->getBallContacts());

		$match->setPlayerWithBall($p2);
		$this->assertSame(1, $p2->getBallContacts());
	}

	public function testSetPlayerWithBallDoesNotIncrementForSamePlayer(): void {
		$home = $this->makeTeam(1);
		$p = $this->makePlayer(10, $home);
		$match = new SimulationMatch(1, $home, $this->makeTeam(2), 1);
		$match->setPlayerWithBall($p);
		$match->setPlayerWithBall($p);
		$this->assertSame(0, $p->getBallContacts());
		$this->assertNull($match->getPreviousPlayerWithBall());
	}

	public function testSetPreviousPlayerWithBall(): void {
		$home = $this->makeTeam(1);
		$p = $this->makePlayer(10, $home);
		$match = new SimulationMatch(1, $home, $this->makeTeam(2), 1);
		$match->setPreviousPlayerWithBall($p);
		$this->assertSame($p, $match->getPreviousPlayerWithBall());
	}

	public function testCleanReferencesUnsetsTeams(): void {
		$home = $this->makeTeam(1);
		$guest = $this->makeTeam(2);
		$match = new SimulationMatch(1, $home, $guest, 1);
		$match->cleanReferences();
		$this->assertFalse(isset($match->homeTeam));
		$this->assertFalse(isset($match->guestTeam));
	}
}
