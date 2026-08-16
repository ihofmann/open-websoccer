<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LastTransfersModel.
 */
final class LastTransfersModelTest extends TestCaseBase {
	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LastTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsCompletedtransfersKey(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LastTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('completedtransfers', $params);
		$this->assertSame([], $params['completedtransfers']);
	}

	public function testGetTemplateParametersForGuestUser(): void {
		$user = $this->makeUser(['id' => null]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws']);
		$model = new LastTransfersModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('completedtransfers', $params);
		$this->assertSame([], $params['completedtransfers']);
	}
}
