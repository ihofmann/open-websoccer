<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CreateYouthMatchRequestController.
 */
final class CreateYouthMatchRequestControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = [], array $cachedRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($selectRowsByTable) {
				foreach ($selectRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($cachedRowsByTable) {
				foreach ($cachedRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $rows;
					}
				}
				return [];
			}
		);
		return $db;
	}

	private function makeWebsoccer(array $config, int $now): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getNowAsTimestamp')->willReturn($now);
		return $ws;
	}

	private function makeUserWithClub(?int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		if ($clubId !== null) {
			$user->setClubId($clubId);
		}
		return $user;
	}

	private function baseConfig(int $now): array {
		$matchdate = $now + 2 * 86400;
		return [
			'youth_enabled' => true, 'youth_matchrequests_enabled' => true, 'db_prefix' => 'ws',
			'youth_matchrequest_max_futuredays' => 7,
			'youth_matchrequest_allowedtimes' => date('H:i', $matchdate),
			'youth_matchrequest_max_open_requests' => 5, 'youth_match_maxperday' => 1,
		];
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$now = 1000000;
		$ws = $this->makeWebsoccer(['youth_enabled' => false, 'youth_matchrequests_enabled' => true, 'db_prefix' => 'ws'], $now);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CreateYouthMatchRequestController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['matchdate' => $now + 86400, 'reward' => 0]));
	}

	public function testExecuteActionThrowsWhenUserHasNoClub(): void {
		$now = 1000000;
		$ws = $this->makeWebsoccer($this->baseConfig($now), $now);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1])); // no club

		$controller = new CreateYouthMatchRequestController(
			$this->mockI18n(['error_action_required_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['matchdate' => $now + 86400, 'reward' => 0]);
	}

	public function testExecuteActionThrowsWhenMatchdateIsInvalid(): void {
		$now = 1000000;
		// allowed times does not contain the actual time of the chosen matchdate
		$cfg = $this->baseConfig($now);
		$cfg['youth_matchrequest_allowedtimes'] = '23:59';
		$ws = $this->makeWebsoccer($cfg, $now);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CreateYouthMatchRequestController(
			$this->mockI18n(['youthteam_matchrequest_create_err_invaliddate' => 'invalid date']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('invalid date');
		$controller->executeAction(['matchdate' => $now + 2 * 86400, 'reward' => 0]);
	}

	public function testExecuteActionCreatesRequestAndReturnsPageId(): void {
		$now = 1000000;
		$matchdate = $now + 2 * 86400;
		$db = $this->makeDb(
			[
				'_youthmatch_request' => [['hits' => 0]],
				'_youthplayer' => [['hits' => 11]],
				'_youthmatch' => [['hits' => 0]],
			]
		);
		$inserted = null;
		$db->method('queryInsert')->willReturnCallback(function ($columns, $fromTable) use (&$inserted) {
			$inserted = ['columns' => $columns, 'fromTable' => $fromTable];
			return null;
		});

		$ws = $this->makeWebsoccer($this->baseConfig($now), $now);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new CreateYouthMatchRequestController(
			$this->mockI18n(['youthteam_matchrequest_create_success' => 'created']), $ws, $db);

		$this->assertSame('youth-matchrequests', $controller->executeAction(['matchdate' => $matchdate, 'reward' => 0]));
		$this->assertSame('ws_youthmatch_request', $inserted['fromTable']);
		$this->assertSame($matchdate, $inserted['columns']['matchdate']);
	}
}
