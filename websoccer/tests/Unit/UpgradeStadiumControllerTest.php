<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for UpgradeStadiumController.
 */
final class UpgradeStadiumControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'stadium_maintenanceinterval_pitch' => 30,
			'stadium_pitch_price' => 10000,
			'stadium_maintenance_priceincrease_per_level' => 10,
		];
	}

	public function testReturnsNullWhenUserHasNoTeam(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		// Guest -> getClubId() returns null -> teamId < 1.
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new UpgradeStadiumController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['type' => 'pitch']));
	}

	public function testThrowsForIllegalType(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal parameter: type');

		$controller = new UpgradeStadiumController($i18n, $ws, $db);
		$controller->executeAction(['type' => 'lawn']);
	}

	public function testThrowsWhenLevelMaxedOut(): void {
		$i18n = $this->mockI18n(['stadium_upgrade_err_not_upgradable' => 'max level']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb([], ['_stadion AS S' => [$this->stadiumRow(['level_pitch' => 5])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('max level');

		$controller = new UpgradeStadiumController($i18n, $ws, $db);
		$controller->executeAction(['type' => 'pitch']);
	}

	public function testUpgradesPitchAndReturnsStadium(): void {
		$i18n = $this->mockI18n([
			'stadium_upgrade_success' => 'ok',
			'stadium_upgrade_success_details' => 'details',
		]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow(['team_budget' => 1000000])]],
			['_stadion AS S' => [$this->stadiumRow(['level_pitch' => 1, 'stadium_id' => 9])]]
		);
		$db->method('queryUpdate');
		$db->method('queryInsert');

		$controller = new UpgradeStadiumController($i18n, $ws, $db);
		$this->assertSame('stadium', $controller->executeAction(['type' => 'pitch']));
	}
}
