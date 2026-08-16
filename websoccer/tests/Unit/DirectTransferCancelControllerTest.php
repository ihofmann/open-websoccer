<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DirectTransferCancelController.
 */
final class DirectTransferCancelControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
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
		return $db;
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['transferoffers_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new DirectTransferCancelController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenOfferNotFound(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->mockWebsoccer(['transferoffers_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new DirectTransferCancelController(
			$this->mockI18n(['transferoffers_offer_cancellation_notfound' => 'not found']), $ws, $this->makeDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not found');
		$controller->executeAction(['id' => 99]);
	}

	public function testExecuteActionDeletesOfferAndReturnsNull(): void {
		$user = $this->makeUser(['id' => 1]);

		$deleted = false;
		$db = $this->makeDb(['_transfer_offer' => [['id' => 5, 'sender_user_id' => 1]]]);
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) {
			$deleted = true;
			return null;
		});

		$ws = $this->mockWebsoccer(['transferoffers_enabled' => true, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$controller = new DirectTransferCancelController(
			$this->mockI18n(['transferoffers_offer_cancellation_success' => 'cancelled']), $ws, $db);

		$this->assertNull($controller->executeAction(['id' => 5]));
		$this->assertTrue($deleted);
	}
}
