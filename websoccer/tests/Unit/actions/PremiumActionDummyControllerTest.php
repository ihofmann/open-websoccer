<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for PremiumActionDummyController.
 */
final class PremiumActionDummyControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testReturnsNullAndAddsSuccessMessage(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer([]);
		$added = [];
		$ws->method('addFrontMessage')->willReturnCallback(function ($m) use (&$added) { $added[] = $m; });
		$db = $this->mockDb();

		$controller = new PremiumActionDummyController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['testparam1' => 'a', 'testparam2' => 'b']));
		$this->assertCount(1, $added);
	}
}
