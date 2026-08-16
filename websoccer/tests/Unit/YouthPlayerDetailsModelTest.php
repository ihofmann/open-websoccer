<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthPlayerDetailsModel.
 */
final class YouthPlayerDetailsModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsTrueWhenYouthEnabled(): void {
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthPlayerDetailsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(1, $model->renderView());
	}

	public function testRenderViewReturnsFalseWhenYouthDisabled(): void {
		$ws = $this->ws(['youth_enabled' => 0, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthPlayerDetailsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenNoPlayerId(): void {
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthPlayerDetailsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsPlayerFromCache(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([['id' => 5, 'firstname' => 'John', 'lastname' => 'Doe']]);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function ($name) { return ($name === 'id') ? 5 : null; });
		$model = new YouthPlayerDetailsModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame(5, $params['player']['id']);
		$this->assertSame('John', $params['player']['firstname']);
	}
}
