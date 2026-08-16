<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SimulationSubstitution.
 */
final class SimulationSubstitutionTest extends TestCaseBase {
	private function makePlayer(int $id): SimulationPlayer {
		$team = new SimulationTeam(1, 50);
		return new SimulationPlayer($id, $team, PLAYER_POSITION_DEFENCE, 'IV', 3.0, 25, 80, 70, 60, 90, 85);
	}

	public function testConstructorSetsBasicProperties(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out);

		$this->assertSame(55, $sub->minute);
		$this->assertSame($in, $sub->playerIn);
		$this->assertSame($out, $sub->playerOut);
		$this->assertNull($sub->condition);
		$this->assertNull($sub->position);
	}

	public function testConstructorWithPosition(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out, null, 'LV');

		$this->assertSame('LV', $sub->position);
	}

	public function testConstructorAcceptsValidConditionTie(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out, SUB_CONDITION_TIE);

		$this->assertSame(SUB_CONDITION_TIE, $sub->condition);
	}

	public function testConstructorAcceptsValidConditionLeading(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out, SUB_CONDITION_LEADING);

		$this->assertSame(SUB_CONDITION_LEADING, $sub->condition);
	}

	public function testConstructorAcceptsValidConditionDeficit(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out, SUB_CONDITION_DEFICIT);

		$this->assertSame(SUB_CONDITION_DEFICIT, $sub->condition);
	}

	public function testConstructorRejectsInvalidCondition(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out, 'InvalidCondition');

		$this->assertNull($sub->condition);
	}

	public function testConstructorRejectsEmptyCondition(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out, '');

		$this->assertNull($sub->condition);
	}

	public function testToStringContainsMinuteAndPlayers(): void {
		$in = $this->makePlayer(1);
		$out = $this->makePlayer(2);
		$sub = new SimulationSubstitution(55, $in, $out);
		$str = (string) $sub;
		$this->assertStringContainsString('minute: 55', $str);
	}
}
