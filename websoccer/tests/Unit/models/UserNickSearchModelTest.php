<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserNickSearchModel.
 */
final class UserNickSearchModelTest extends TestCaseBase {
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
		$model = new UserNickSearchModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyItemsWhenNoMatches(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'query') ? 'xyz' : null; });
		$model = new UserNickSearchModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('items', $params);
		$this->assertSame([], $params['items']);
	}

	public function testGetTemplateParametersReturnsUserNicksFromDb(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['nick' => 'bob'],
			['nick' => 'bobby'],
		]));
		$ws = $this->ws(['db_prefix' => 'ws'], function ($name) { return ($name === 'query') ? 'bob' : null; });
		$model = new UserNickSearchModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(['bob', 'bobby'], $params['items']);
	}
}
