<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for SellYouthPlayerController.
 */
final class SellYouthPlayerControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private function config(): array {
		return ['db_prefix' => 'ws', 'youth_enabled' => TRUE];
	}

	public function testReturnsNullWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(array_merge($this->config(), ['youth_enabled' => FALSE]));
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new SellYouthPlayerController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1, 'transfer_fee' => 1000]));
	}

	public function testSellsOwnYouthPlayerAndReturnsYouthTeam(): void {
		$i18n = $this->mockI18n(['youthteam_sell_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_youthplayer' => [$this->youthPlayerRow(['team_id' => 1, 'transfer_fee' => 0])]]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new SellYouthPlayerController($i18n, $ws, $db);
		$this->assertSame('youth-team', $controller->executeAction(['id' => 1, 'transfer_fee' => 5000]));
		$this->assertSame(5000, $updated[0]['transfer_fee']);
	}

	public function testThrowsWhenAlreadyOnMarket(): void {
		$i18n = $this->mockI18n(['youthteam_sell_err_alreadyonmarket' => 'on market']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_youthplayer' => [$this->youthPlayerRow(['team_id' => 1, 'transfer_fee' => 100])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('on market');

		$controller = new SellYouthPlayerController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1, 'transfer_fee' => 5000]);
	}

	public function testThrowsWhenNotOwnPlayer(): void {
		$i18n = $this->mockI18n(['youthteam_err_notownplayer' => 'not own']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_youthplayer' => [$this->youthPlayerRow(['team_id' => 2])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not own');

		$controller = new SellYouthPlayerController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1, 'transfer_fee' => 5000]);
	}
}
