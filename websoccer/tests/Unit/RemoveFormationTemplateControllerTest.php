<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for RemoveFormationTemplateController.
 */
final class RemoveFormationTemplateControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testRemovesOwnTemplateAndReturnsNull(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb([], ['_aufstellung' => [['verein_id' => 1]]]);

		$deleted = false;
		$db->method('queryDelete')->willReturnCallback(function () use (&$deleted) { $deleted = true; });

		$controller = new RemoveFormationTemplateController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['templateid' => 5]));
		$this->assertTrue($deleted);
	}

	public function testThrowsForForeignTemplate(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->makeDb([], ['_aufstellung' => [['verein_id' => 2]]]);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal template ID');

		$controller = new RemoveFormationTemplateController($i18n, $ws, $db);
		$controller->executeAction(['templateid' => 5]);
	}

	public function testThrowsWhenTemplateNotFound(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('illegal template ID');

		$controller = new RemoveFormationTemplateController($i18n, $ws, $db);
		$controller->executeAction(['templateid' => 99]);
	}
}
