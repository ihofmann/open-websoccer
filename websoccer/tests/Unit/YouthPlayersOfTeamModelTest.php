<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthPlayersOfTeamModel.
 */
final class YouthPlayersOfTeamModelTest extends TestCaseBase {
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
		$model = new YouthPlayersOfTeamModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(1, $model->renderView());
	}

	public function testRenderViewReturnsFalseWhenYouthDisabled(): void {
		$ws = $this->ws(['youth_enabled' => 0, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthPlayersOfTeamModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyPlayersWhenNoTeamId(): void {
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function () { return null; });
		$model = new YouthPlayersOfTeamModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('players', $params);
		$this->assertSame([], $params['players']);
	}

	public function testGetTemplateParametersReturnsPlayersFromCache(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([
			['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe'],
			['id' => 2, 'firstname' => 'Jane', 'lastname' => 'Smith'],
		]);
		$ws = $this->ws(['youth_enabled' => 1, 'db_prefix' => 'ws'], function ($name) { return ($name === 'teamid') ? 10 : null; });
		$model = new YouthPlayersOfTeamModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['players']);
	}
}
