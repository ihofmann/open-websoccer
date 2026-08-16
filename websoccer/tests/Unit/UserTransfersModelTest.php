<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserTransfersModel.
 */
final class UserTransfersModelTest extends TestCaseBase {
	private function ws(array $config, $requestCb): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function () { return null; });
		$model = new UserTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyTransfersWhenNoData(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('completedtransfers', $params);
		$this->assertSame([], $params['completedtransfers']);
	}

	public function testGetTemplateParametersReturnsTransfersFromDb(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['transfer_date' => 100, 'player_id' => 1, 'player_firstname' => 'John',
				'player_lastname' => 'Doe', 'from_id' => 10, 'from_name' => 'A',
				'to_id' => 20, 'to_name' => 'B', 'directtransfer_amount' => 500],
		]));
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserTransfersModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['completedtransfers']);
	}
}
