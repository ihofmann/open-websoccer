<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for YouthMatchRequestsCreateModel.
 */
final class YouthMatchRequestsCreateModelTest extends TestCaseBase {
	private function ws(array $config): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	public function testRenderViewReturnsTrueWhenYouthEnabled(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequest_max_futuredays' => 3,
			'youth_matchrequest_allowedtimes' => '18:00,20:00', 'datetime_format' => 'Y-m-d H:i',
		]);
		$model = new YouthMatchRequestsCreateModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(1, $model->renderView());
	}

	public function testRenderViewReturnsFalseWhenYouthDisabled(): void {
		$ws = $this->ws([
			'youth_enabled' => 0, 'youth_matchrequest_max_futuredays' => 3,
			'youth_matchrequest_allowedtimes' => '18:00,20:00', 'datetime_format' => 'Y-m-d H:i',
		]);
		$model = new YouthMatchRequestsCreateModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame(0, $model->renderView());
	}

	public function testGetTemplateParametersReturnsDateOptions(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequest_max_futuredays' => 2,
			'youth_matchrequest_allowedtimes' => '18:00,20:00', 'datetime_format' => 'Y-m-d H:i',
		]);
		$model = new YouthMatchRequestsCreateModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('dateOptions', $params);
		// 2 days * 2 times per day = 4 options
		$this->assertCount(4, $params['dateOptions']);
	}

	public function testGetTemplateParametersReturnsEmptyDateOptionsWhenNoDays(): void {
		$ws = $this->ws([
			'youth_enabled' => 1, 'youth_matchrequest_max_futuredays' => 0,
			'youth_matchrequest_allowedtimes' => '18:00', 'datetime_format' => 'Y-m-d H:i',
		]);
		$model = new YouthMatchRequestsCreateModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame([], $params['dateOptions']);
	}
}
