<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TeamTransfersModel.
 */
final class TeamTransfersModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function() { return null; });
		$model = new TeamTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyTransfersWhenTeamHasNone(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], function($name) { return ($name === 'teamid') ? 5 : null; });
		$model = new TeamTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['completedtransfers']);
	}
}
