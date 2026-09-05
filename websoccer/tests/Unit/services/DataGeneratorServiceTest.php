<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for DataGeneratorService.
 */
final class DataGeneratorServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(array_merge(['db_prefix' => 'ws'], $config));
	}

	public function testGenerateTeamsThrowsWhenLeagueNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->mockDb();
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('illegal league ID');
		DataGeneratorService::generateTeams($ws, $db, 2, 999, 1000000, false, 'Stadion %s', 1000, 2000, 500, 1000, 100);
	}

	public function testGeneratePlayersThrowsWhenTeamNotFoundAndNoNationality(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->mockDb();
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('illegal team ID');
		DataGeneratorService::generatePlayers($ws, $db, 999, 25, 2, 5000, 30,
			['strength' => 80, 'technique' => 70, 'stamina' => 60, 'freshness' => 50, 'satisfaction' => 40],
			['T' => 1], 5, null);
	}

	public function testGeneratePlayersThrowsWhenNameFileDoesNotExist(): void {
		$ws = $this->makeWebsoccer(['supported_languages' => 'en']);
		$i18n = $this->mockI18n(['generator_err_filedoesnotexist' => 'File does not exist: %s']);
		\WebSoccer::setInstanceForTesting($ws);
		\I18n::setInstanceForTesting($i18n);

		$db = $this->mockDb();
		try {
			DataGeneratorService::generatePlayers($ws, $db, 0, 25, 2, 5000, 30,
				['strength' => 80, 'technique' => 70, 'stamina' => 60, 'freshness' => 50, 'satisfaction' => 40],
				['T' => 1], 5, 'NonexistentCountry');
			$this->fail('Expected exception was not thrown.');
		} catch (\Exception $e) {
			$this->assertStringStartsWith('File does not exist:', $e->getMessage());
		}
	}
}
