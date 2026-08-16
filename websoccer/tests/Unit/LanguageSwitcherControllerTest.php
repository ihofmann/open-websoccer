<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for LanguageSwitcherController.
 */
final class LanguageSwitcherControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	private string $msgFile = '';
	private string $entityFile = '';

	protected function setUp(): void {
		parent::setUp();
		// The controller includes the generated message cache files for the
		// chosen language. Provide minimal stubs so the include() succeeds.
		$this->msgFile = BASE_FOLDER . '/cache/messages_en.inc.php';
		$this->entityFile = BASE_FOLDER . '/cache/entitymessages_en.inc.php';
		file_put_contents($this->msgFile, '<?php $msg = [];');
		file_put_contents($this->entityFile, '<?php $msg = [];');
	}

	protected function tearDown(): void {
		foreach ([$this->msgFile, $this->entityFile] as $f) {
			if (is_file($f)) {
				unlink($f);
			}
		}
		parent::tearDown();
	}

	public function testReturnsNullAndPersistsLanguageForLoggedUser(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 5]));

		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->once())->method('queryUpdate')
			->with($this->callback(function ($c) { return isset($c['lang']) && $c['lang'] === 'en'; }),
				'ws_user', 'id = %d', 5);

		$controller = new LanguageSwitcherController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['lang' => 'EN']));
	}

	public function testReturnsNullAndDoesNotUpdateForGuest(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser([]));

		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$controller = new LanguageSwitcherController($i18n, $ws, $db);
		$this->assertNull($controller->executeAction(['lang' => 'en']));
	}
}
