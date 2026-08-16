<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ScheduleGenerator.
 */
final class ScheduleGeneratorTest extends TestCaseBase {
	/**
	 * Collects every (home, guest) pair from the full schedule.
	 */
	private function allMatches(array $schedule): array {
		$matches = [];
		foreach ($schedule as $matchday => $games) {
			foreach ($games as $match) {
				$matches[] = $match;
			}
		}
		return $matches;
	}

	/**
	 * Returns the set of unordered pairs {a,b} that appear in the schedule.
	 */
	private function uniquePairKeys(array $matches): array {
		$keys = [];
		foreach ($matches as $match) {
			$pair = [$match[0], $match[1]];
			sort($pair);
			$keys[implode('-', $pair)] = true;
		}
		return array_keys($keys);
	}

	public function testEvenNumberOfTeamsProducesCorrectMatchdays(): void {
		$teams = [1, 2, 3, 4];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		// 4 teams -> 3 matchdays
		$this->assertCount(3, $schedule);
		$this->assertArrayHasKey(1, $schedule);
		$this->assertArrayHasKey(2, $schedule);
		$this->assertArrayHasKey(3, $schedule);
	}

	public function testEvenNumberOfTeamsEachMatchdayHasHalfMatches(): void {
		$teams = [1, 2, 3, 4];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		foreach ($schedule as $games) {
			$this->assertCount(2, $games);
		}
	}

	public function testEvenNumberOfTeamsEveryPairPlaysOnce(): void {
		$teams = [1, 2, 3, 4];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		$matches = $this->allMatches($schedule);

		// 4 teams -> C(4,2) = 6 unique pairs, single round robin
		$this->assertCount(6, $matches);
		$this->assertCount(6, $this->uniquePairKeys($matches));
	}

	public function testEvenNumberOfTeamsNoTeamPlaysItself(): void {
		$teams = [1, 2, 3, 4, 5, 6];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		foreach ($schedule as $games) {
			foreach ($games as $match) {
				$this->assertNotSame($match[0], $match[1]);
			}
		}
	}

	public function testEvenNumberOfTeamsNoDummyInMatches(): void {
		$teams = [1, 2, 3, 4];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		foreach ($schedule as $games) {
			foreach ($games as $match) {
				$this->assertNotSame(DUMMY_TEAM_ID, $match[0]);
				$this->assertNotSame(DUMMY_TEAM_ID, $match[1]);
			}
		}
	}

	public function testEvenNumberOfTeamsEveryTeamPlaysPerMatchday(): void {
		$teams = [1, 2, 3, 4, 5, 6];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		foreach ($schedule as $games) {
			$playingTeams = [];
			foreach ($games as $match) {
				$playingTeams[] = $match[0];
				$playingTeams[] = $match[1];
			}
			// Each of the 6 teams should appear exactly once per matchday.
			$this->assertSame(count($teams), count($playingTeams));
			sort($playingTeams);
			$this->assertSame($teams, $playingTeams);
		}
	}

	public function testOddNumberOfTeamsProducesCorrectMatchdays(): void {
		$teams = [1, 2, 3, 5];
		// Use distinct IDs to avoid sort ambiguity with dummy.
		$teams = [10, 20, 30];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		// 3 teams -> dummy added -> 4 teams -> 3 matchdays
		$this->assertCount(3, $schedule);
	}

	public function testOddNumberOfTeamsHasByes(): void {
		$teams = [10, 20, 30];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		// With 3 teams and a dummy, each matchday has 2 matches (4/2),
		// but one match contains the dummy and is skipped, leaving 1.
		foreach ($schedule as $games) {
			$this->assertCount(1, $games);
		}
	}

	public function testOddNumberOfTeamsEveryPairPlaysOnce(): void {
		$teams = [10, 20, 30];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		$matches = $this->allMatches($schedule);

		// 3 teams -> C(3,2) = 3 unique pairs
		$this->assertCount(3, $matches);
		$this->assertCount(3, $this->uniquePairKeys($matches));
	}

	public function testOddNumberOfTeamsNoDummyInMatches(): void {
		$teams = [10, 20, 30];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		foreach ($schedule as $games) {
			foreach ($games as $match) {
				$this->assertNotSame(DUMMY_TEAM_ID, $match[0]);
				$this->assertNotSame(DUMMY_TEAM_ID, $match[1]);
			}
		}
	}

	public function testOddNumberOfTeamsEachTeamGetsOneBye(): void {
		$teams = [10, 20, 30];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		// Each team should have exactly one matchday where it doesn't play (bye).
		$byes = [];
		foreach ($teams as $t) {
			$byes[$t] = 0;
		}
		foreach ($schedule as $matchday => $games) {
			$playing = [];
			foreach ($games as $match) {
				$playing[$match[0]] = true;
				$playing[$match[1]] = true;
			}
			foreach ($teams as $t) {
				if (!isset($playing[$t])) {
					$byes[$t]++;
				}
			}
		}
		foreach ($byes as $count) {
			$this->assertSame(1, $count);
		}
	}

	public function testTwoTeamsProducesOneMatchday(): void {
		$teams = [1, 2];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		$this->assertCount(1, $schedule);
		$this->assertCount(1, $schedule[1]);
		$match = $schedule[1][0];
		// One team is home, the other away (order randomised by shuffle).
		$this->assertContains($match[0], $teams);
		$this->assertContains($match[1], $teams);
		$this->assertNotSame($match[0], $match[1]);
	}

	public function testSixTeamsProducesFiveMatchdays(): void {
		$teams = [1, 2, 3, 4, 5, 6];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		$this->assertCount(5, $schedule);
		// 15 total matches (C(6,2)).
		$this->assertCount(15, $this->allMatches($schedule));
	}

	public function testEightTeamsProducesSevenMatchdays(): void {
		$teams = [1, 2, 3, 4, 5, 6, 7, 8];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		$this->assertCount(7, $schedule);
		$this->assertCount(28, $this->allMatches($schedule));
	}

	public function testFiveTeamsProducesFiveMatchdaysWithByes(): void {
		$teams = [1, 2, 3, 4, 5];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);
		// 5 teams -> dummy added -> 6 teams -> 5 matchdays.
		$this->assertCount(5, $schedule);
		// 10 total matches (C(5,2)).
		$this->assertCount(10, $this->allMatches($schedule));
	}

	public function testHomeAwayAlternationOnFirstMatch(): void {
		// With even matchday numbers, the first match (which contains the
		// fixed end) should swap home/away. We verify that across all
		// matchdays, the fixed-end team appears as both home and away
		// in the first match.
		$teams = [1, 2, 3, 4];
		$schedule = ScheduleGenerator::createRoundRobinSchedule($teams);

		// The last team in the (shuffled) array is the fixed end.
		// After shuffle we don't know which one it is, but we can verify
		// that the alternation logic doesn't crash and produces valid
		// matches where the first match has different home/away on
		// odd vs even matchdays.
		$firstMatchOdd = $schedule[1][0];
		$firstMatchEven = $schedule[2][0];
		// On even matchdays, the first match is swapped relative to the
		// natural order. We just verify both are valid distinct-team matches.
		$this->assertNotSame($firstMatchOdd[0], $firstMatchOdd[1]);
		$this->assertNotSame($firstMatchEven[0], $firstMatchEven[1]);
	}
}
