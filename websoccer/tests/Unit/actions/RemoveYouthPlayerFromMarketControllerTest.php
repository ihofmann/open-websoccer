<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for RemoveYouthPlayerFromMarketController.
 */
final class RemoveYouthPlayerFromMarketControllerTest extends TestCaseBase {
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

		$controller = new RemoveYouthPlayerFromMarketController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['id' => 1]));
	}

	public function testRemovesOwnYouthPlayerFromMarketAndReturnsYouthTeam(): void {
		$i18n = $this->mockI18n(['youthteam_removefrommarket_success' => 'ok']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_youthplayer' => [$this->youthPlayerRow(['team_id' => 1])]]);

		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($c, $t, $w, $p) use (&$updated) { $updated = [$c, $t, $w, $p]; });

		$controller = new RemoveYouthPlayerFromMarketController($i18n, $ws, $db);
		$this->assertSame('youth-team', $controller->executeAction(['id' => 1]));
		$this->assertSame(0, $updated[0]['transfer_fee']);
	}

	public function testThrowsWhenNotOwnPlayer(): void {
		$i18n = $this->mockI18n(['youthteam_err_notownplayer' => 'not own']);
		$ws = $this->mockWebsoccer($this->config());
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb(['_youthplayer' => [$this->youthPlayerRow(['team_id' => 2])]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('not own');

		$controller = new RemoveYouthPlayerFromMarketController($i18n, $ws, $db);
		$controller->executeAction(['id' => 1]);
	}
}
