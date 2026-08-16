<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LastMatchModel.
 */
final class LastMatchModelTest extends TestCaseBase {
	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LastMatchModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsLastMatchKey(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LastMatchModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('last_match', $params);
		$this->assertSame([], $params['last_match']);
	}

	public function testGetTemplateParametersForGuestUser(): void {
		$user = $this->makeUser(['id' => null]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LastMatchModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('last_match', $params);
		$this->assertSame([], $params['last_match']);
	}
}
