<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ClubSearchModel.
 */
final class ClubSearchModelTest extends TestCaseBase {
	private function websoccerWithRequest(array $request, array $config = []): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback(function ($name) use ($request) {
			return $request[$name] ?? null;
		});
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->websoccerWithRequest([], ['db_prefix' => 'ws']);
		$model = new ClubSearchModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsItemsKey(): void {
		$ws = $this->websoccerWithRequest(['query' => 'foo'], ['db_prefix' => 'ws']);
		$model = new ClubSearchModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('items', $params);
		$this->assertSame([], $params['items']);
	}

	public function testGetTemplateParametersWithNullQuery(): void {
		$ws = $this->websoccerWithRequest([], ['db_prefix' => 'ws']);
		$model = new ClubSearchModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('items', $params);
	}
}
