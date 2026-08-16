<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserActivitiesModel.
 */
final class UserActivitiesModelTest extends TestCaseBase {
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
		$model = new UserActivitiesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyActivitiesWhenNoData(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserActivitiesModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('activities', $params);
		$this->assertSame([], $params['activities']);
	}

	public function testGetTemplateParametersReturnsActivitiesFromDb(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['log_id' => 1, 'action_id' => 'login', 'user_id' => 5, 'created_date' => 100, 'user_name' => 'bob'],
		]));
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'userid') ? 5 : null; });
		$model = new UserActivitiesModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['activities']);
		$this->assertSame('login', $params['activities'][0]['action_id']);
	}
}
