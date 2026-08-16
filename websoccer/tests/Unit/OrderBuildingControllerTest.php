<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for OrderBuildingController.
 */
final class OrderBuildingControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return ['db_prefix' => 'ws'];
	}

	public function testThrowsWhenUserHasNoTeam(): void {
		$i18n = $this->mockI18n(['feature_requires_team' => 'requires team']);
		$ws = $this->mockWebsoccer($this->config());
		// Guest -> getClubId() returns null.
		$ws->method('getUser')->willReturn($this->makeUser([]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('requires team');

		$controller = new OrderBuildingController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}

	public function testThrowsForIllegalBuilding(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		// No building row found.
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal building.');

		$controller = new OrderBuildingController($i18n, $ws, $db);
		$controller->executeAction(['id' => 99]);
	}

	public function testThrowsWhenBuildingAlreadyExists(): void {
		$i18n = $this->mockI18n(['stadiumenvironment_build_err_already_exists' => 'exists']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow(['team_budget' => 1000000])]],
			[
				'ws_stadiumbuilding' => [['id' => 1, 'costs' => 1000, 'required_building_id' => 0, 'premiumfee' => 0, 'effect_fanpopularity' => 0, 'name' => 'Shop', 'construction_time_days' => 2]],
				'ws_buildings_of_team' => [['team_id' => 1, 'building_id' => 1]],
			]
		);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('exists');

		$controller = new OrderBuildingController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}

	public function testOrdersBuildingAndReturnsNull(): void {
		$i18n = $this->mockI18n(['stadiumenvironment_build_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow(['team_budget' => 1000000])]],
			[
				'ws_stadiumbuilding' => [['id' => 1, 'costs' => 1000, 'required_building_id' => 0, 'premiumfee' => 0, 'effect_fanpopularity' => 0, 'name' => 'Shop', 'construction_time_days' => 2]],
				// No existing building of team, no required building needed.
				'ws_buildings_of_team' => [],
			]
		);
		$inserted = false;
		$db->method('queryInsert')->willReturnCallback(function () use (&$inserted) { $inserted = true; });
		$db->method('queryUpdate');

		$controller = new OrderBuildingController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
		$this->assertTrue($inserted);
	}
}
