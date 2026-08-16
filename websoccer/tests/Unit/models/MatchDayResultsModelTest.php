<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for MatchDayResultsModel.
 */
final class MatchDayResultsModelTest extends TestCaseBase {
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

	public function testRenderViewReturnsFalseWhenNoParameters(): void {
		$ws = $this->websoccerWithRequest([], ['db_prefix' => 'ws']);
		$model = new MatchDayResultsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenOnlySeasonIdProvided(): void {
		$ws = $this->websoccerWithRequest(['seasonid' => 5], ['db_prefix' => 'ws']);
		$model = new MatchDayResultsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueWhenSeasonAndMatchdayProvided(): void {
		$ws = $this->websoccerWithRequest(['seasonid' => 5, 'matchday' => 3], ['db_prefix' => 'ws']);
		$model = new MatchDayResultsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsMatchesKey(): void {
		$ws = $this->websoccerWithRequest(['seasonid' => 5, 'matchday' => 3], ['db_prefix' => 'ws']);
		$model = new MatchDayResultsModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('matches', $params);
		$this->assertSame([], $params['matches']);
	}
}
