<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for ShoutboxModel.
 */
final class ShoutboxModelTest extends TestCaseBase {
	private function ws(array $config, $requestCb): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback($requestCb);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewReturnsFalseWhenNoId(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function() { return null; });
		$model = new ShoutboxModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenIdProvided(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function($name) { return ($name === 'id') ? 7 : null; });
		$model = new ShoutboxModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyMessages(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function($name) { return ($name === 'id') ? 7 : null; });
		$model = new ShoutboxModel($this->mockDb(), $this->mockI18n(), $ws);
		$model->renderView();
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['messages']);
	}
}
