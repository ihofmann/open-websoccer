<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for RemoveNationalPlayerController.
 */
final class RemoveNationalPlayerControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return ['db_prefix' => 'ws', 'nationalteams_enabled' => TRUE];
	}

	public function testReturnsNullWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['nationalteams_enabled' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryDelete');

		$controller = new RemoveNationalPlayerController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
	}

	public function testThrowsWhenUserManagesNoNationalTeam(): void {
		$i18n = $this->mockI18n(['nationalteams_user_requires_team' => 'requires team']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		// getNationalTeamManagedByCurrentUser -> no rows.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('requires team');

		$controller = new RemoveNationalPlayerController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}

	public function testRemovesPlayerFromNationalTeamAndReturnsNationalteam(): void {
		$i18n = $this->mockI18n(['nationalteams_removeplayer_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->makeDb(
			// national team id (cached) + team name (select) share the _verein table.
			['_verein' => [['id' => 10]]],
			[
				'ws_verein' => [['name' => 'Germany']],
				'ws_spieler' => [['nation' => 'Germany']],
			]
		);

		$deleted = null;
		$db->method('queryDelete')->willReturnCallback(function ($t, $w, $p) use (&$deleted) { $deleted = [$t, $w, $p]; });

		$controller = new RemoveNationalPlayerController($i18n, $ws, $db);
		$this->assertSame('nationalteam', $controller->executeAction(['id' => 7]));
		$this->assertSame('ws_nationalplayer', $deleted[0]);
	}

	public function testThrowsWhenPlayerFromDifferentNation(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->makeDb(
			['_verein' => [['id' => 10]]],
			[
				'ws_verein' => [['name' => 'Germany']],
				'ws_spieler' => [['nation' => 'France']],
			]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Player is from different nation.');

		$controller = new RemoveNationalPlayerController($i18n, $ws, $db);
		$controller->executeAction(['id' => 7]);
	}
}
