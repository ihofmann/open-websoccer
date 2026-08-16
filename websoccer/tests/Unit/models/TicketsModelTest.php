<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TicketsModel.
 */
final class TicketsModelTest extends TestCaseBase {
	private function dbMock(array $rows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			return $this->dbResult($rows);
		});
		return $db;
	}

	private function ticketRow(): array {
		return [
			'p_stands' => 10, 'p_seats' => 20, 'p_stands_grand' => 30, 'p_seats_grand' => 40, 'p_vip' => 100,
			'l_stands' => 0, 'l_seats' => 0, 'l_stands_grand' => 0, 'l_seats_grand' => 0, 'l_vip' => 0,
			's_stands' => 1000, 's_seats' => 500, 's_stands_grand' => 200, 's_seats_grand' => 100, 's_vip' => 50
		];
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new TicketsModel($this->dbMock(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$model = new TicketsModel($this->dbMock(), $this->mockI18n(['feature_requires_team' => 'need team']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsTicketPrices(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(5);
		$ws->method('getUser')->willReturn($user);
		$model = new TicketsModel($this->dbMock([$this->ticketRow()]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(10, $params['tickets']['p_stands']);
		$this->assertSame(100, $params['tickets']['p_vip']);
	}
}
