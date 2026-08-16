<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ExchangePremiumController.
 */
final class ExchangePremiumControllerTest extends TestCaseBase {
	private function makeUserWithClub(?int $clubId, int $premiumBalance = 0): \User {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager', 'premiumBalance' => $premiumBalance]);
		if ($clubId !== null) {
			$user->setClubId($clubId);
		}
		return $user;
	}

	public function testExecuteActionThrowsWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['premium_exchangerate_gamecurrency' => 0, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1, 100));

		$controller = new ExchangePremiumController($this->mockI18n(), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('featue is disabled!');
		$controller->executeAction(['amount' => 10, 'validateonly' => 0]);
	}

	public function testExecuteActionThrowsWhenUserHasNoClub(): void {
		$ws = $this->mockWebsoccer(['premium_exchangerate_gamecurrency' => 10, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(null, 100));

		$controller = new ExchangePremiumController(
			$this->mockI18n(['feature_requires_team' => 'requires team']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('requires team');
		$controller->executeAction(['amount' => 10, 'validateonly' => 0]);
	}

	public function testExecuteActionThrowsWhenBalanceNotEnough(): void {
		$ws = $this->mockWebsoccer(['premium_exchangerate_gamecurrency' => 10, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1, 5));

		$controller = new ExchangePremiumController(
			$this->mockI18n(['premium-exchange_err_balancenotenough' => 'balance not enough']), $ws, $this->mockDb());

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('balance not enough');
		$controller->executeAction(['amount' => 10, 'validateonly' => 0]);
	}

	public function testExecuteActionReturnsConfirmationPageWhenValidateOnly(): void {
		$ws = $this->mockWebsoccer(['premium_exchangerate_gamecurrency' => 10, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1, 100));

		$controller = new ExchangePremiumController($this->mockI18n(), $ws, $this->mockDb());

		$this->assertSame('premium-exchange-confirm', $controller->executeAction(['amount' => 10, 'validateonly' => 1]));
	}
}
