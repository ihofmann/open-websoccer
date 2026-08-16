<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for ExtendStadiumController.
 */
final class ExtendStadiumControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return [
			'db_prefix' => 'ws',
			'stadium_max_side' => 10000,
			'stadium_max_grand' => 10000,
			'stadium_max_vip' => 10000,
			'stadium_cost_standing' => 50,
			'stadium_cost_seats' => 100,
			'stadium_cost_standing_grand' => 75,
			'stadium_cost_seats_grand' => 150,
			'stadium_cost_vip' => 500,
			'no_transactions_for_teams_without_user' => FALSE,
		];
	}

	/**
	 * Builds a fresh WebSoccer mock with a configurable getRequestParameter map.
	 */
	private function makeWs(array $config, \User $user, int $now, array $requestParams): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getUser')->willReturn($user);
		$ws->method('getNowAsTimestamp')->willReturn($now);
		$ws->method('getRequestParameter')->willReturnCallback(function ($name) use ($requestParams) {
			return array_key_exists($name, $requestParams) ? $requestParams[$name] : null;
		});
		return $ws;
	}

	public function testReturnsNullWhenUserHasNoTeam(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->makeWs($this->config(), $this->makeUser([]), 1000000, []);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryInsert');

		$controller = new ExtendStadiumController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction([
			'side_standing' => 100, 'side_seats' => 0, 'grand_standing' => 0,
			'grand_seats' => 0, 'vip' => 0,
		]));
	}

	public function testReturnsNullWhenNoSeatsEntered(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->makeWs($this->config(), $this->makeLoggedUser(1, 1), 1000000, []);
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryInsert');

		$controller = new ExtendStadiumController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction([
			'side_standing' => 0, 'side_seats' => 0, 'grand_standing' => 0,
			'grand_seats' => 0, 'vip' => 0,
		]));
	}

	public function testThrowsWhenSideSeatsExceedMaximum(): void {
		$i18n = $this->mockI18n(['stadium_extend_err_exceed_max_side' => 'max side %s']);
		$ws = $this->makeWs(
			array_merge($this->config(), ['stadium_max_side' => 1000]),
			$this->makeLoggedUser(1, 1), 1000000, []
		);
		// Existing side seats (1000 + 500 = 1500) + 100 new = 1600 > 1000.
		$db = $this->makeDb([], [
			'_stadion AS S' => [$this->stadiumRow(['places_stands' => 1000, 'places_seats' => 500])],
		]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('max side 1000');

		$controller = new ExtendStadiumController($i18n, $ws, $db);
		$controller->executeAction([
			'side_standing' => 100, 'side_seats' => 0, 'grand_standing' => 0,
			'grand_seats' => 0, 'vip' => 0,
		]);
	}

	public function testValidateOnlyReturnsConfirmPage(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->makeWs($this->config(), $this->makeLoggedUser(1, 1), 1000000, []);
		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow()]],
			[
				'_stadion AS S' => [$this->stadiumRow()],
				'_stadium_construction AS C' => [], // no on-going construction
			]
		);
		$db->expects($this->never())->method('queryInsert');

		$controller = new ExtendStadiumController($i18n, $ws, $db);
		$this->assertSame('stadium-extend-confirm', $controller->executeAction([
			'side_standing' => 100, 'side_seats' => 0, 'grand_standing' => 0,
			'grand_seats' => 0, 'vip' => 0, 'validate-only' => 1,
		]));
	}

	public function testCreatesConstructionOrderAndReturnsStadium(): void {
		$now = 1000000;
		$i18n = $this->mockI18n(['stadium_extend_success' => 'built']);
		$user = $this->makeLoggedUser(1, 1);
		$requestParams = [
			'offerid' => 1,
			'side_standing' => 100, 'side_seats' => 0,
			'grand_standing' => 0, 'grand_seats' => 0, 'vip' => 0,
		];
		$ws = $this->makeWs($this->config(), $user, $now, $requestParams);

		$builderRow = [
			'id' => 1, 'name' => 'Builder', 'picture' => '', 'premiumfee' => 0,
			'fixedcosts' => 100, 'cost_per_seat' => 10, 'reliability' => 90,
			'construction_time_days_min' => 1, 'construction_time_days' => 1,
			'min_stadium_size' => 0, 'max_stadium_size' => 0,
		];

		$db = $this->makeDb(
			['_verein AS C' => [$this->teamRow(['team_budget' => 1000000])]],
			[
				'_stadion AS S' => [$this->stadiumRow()],
				'_stadium_construction AS C' => [],
				'_stadium_builder' => [$builderRow],
				'_useractionlog' => [],
				'_badge' => [],
			]
		);

		$inserts = [];
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserts) {
			$inserts[] = $fromTable;
		});

		$controller = new ExtendStadiumController($i18n, $ws, $db);
		$this->assertSame('stadium', $controller->executeAction([
			'side_standing' => 100, 'side_seats' => 0, 'grand_standing' => 0,
			'grand_seats' => 0, 'vip' => 0,
		]));

		// Construction order + bank-account statement + action-log entry.
		$this->assertContains('ws_stadium_construction', $inserts);
		$this->assertContains('ws_konto', $inserts);
	}
}
