<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for AdsModel.
 */
final class AdsModelTest extends TestCaseBase {
	private function websoccerWithUser(\User $user, array $config = []): \WebSoccer {
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsTrueWhenUserHasNoPremiumBalance(): void {
		$user = $this->makeUser(['id' => 1, 'premiumBalance' => 0]);
		$ws = $this->websoccerWithUser($user, ['frontend_ads_hide_for_premiumusers' => TRUE]);
		$model = new AdsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseForPremiumUserWhenAdsHidden(): void {
		$user = $this->makeUser(['id' => 1, 'premiumBalance' => 5]);
		$ws = $this->websoccerWithUser($user, ['frontend_ads_hide_for_premiumusers' => TRUE]);
		$model = new AdsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueForPremiumUserWhenAdsNotHidden(): void {
		$user = $this->makeUser(['id' => 1, 'premiumBalance' => 5]);
		$ws = $this->websoccerWithUser($user, ['frontend_ads_hide_for_premiumusers' => FALSE]);
		$model = new AdsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyArray(): void {
		$user = $this->makeUser(['id' => 1, 'premiumBalance' => 0]);
		$ws = $this->websoccerWithUser($user, ['frontend_ads_hide_for_premiumusers' => FALSE]);
		$model = new AdsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}
}
