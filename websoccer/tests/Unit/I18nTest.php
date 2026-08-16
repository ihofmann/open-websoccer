<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for I18n.
 */
final class I18nTest extends TestCaseBase {
	private ?array $savedMsg = null;

	protected function setUp(): void {
		parent::setUp();
		// Save and clear the global $msg used by getMessage/hasMessage.
		if (isset($GLOBALS['msg'])) {
			$this->savedMsg = $GLOBALS['msg'];
		}
		$GLOBALS['msg'] = [];
	}

	protected function tearDown(): void {
		// Restore global $msg.
		if ($this->savedMsg !== null) {
			$GLOBALS['msg'] = $this->savedMsg;
		} else {
			unset($GLOBALS['msg']);
		}
		$this->savedMsg = null;
		parent::tearDown();
	}

	/**
	 * Creates a real I18n instance via getInstance (bypassing the private
	 * constructor) for the given supported languages.
	 */
	private function makeI18n(string $supportedLanguages): \I18n {
		\I18n::setInstanceForTesting(null);
		return \I18n::getInstance($supportedLanguages);
	}

	public function testGetInstanceCreatesSingleton(): void {
		$i18n = $this->makeI18n('en,de');
		$this->assertSame($i18n, \I18n::getInstance('en,de'));
	}

	public function testSetInstanceForTestingReplacesInstance(): void {
		$mock = $this->mockI18n(['foo' => 'bar']);
		\I18n::setInstanceForTesting($mock);
		$this->assertSame($mock, \I18n::getInstance('en'));
	}

	public function testSetInstanceForTestingNullClearsInstance(): void {
		$i18n = $this->makeI18n('en');
		\I18n::setInstanceForTesting(null);
		// A new instance should be created on next getInstance call.
		$new = \I18n::getInstance('en');
		$this->assertNotSame($i18n, $new);
	}

	public function testGetSupportedLanguagesReturnsTrimmedArray(): void {
		$i18n = $this->makeI18n('en, de , fr');
		$this->assertSame(['en', 'de', 'fr'], $i18n->getSupportedLanguages());
	}

	public function testGetCurrentLanguageReturnsDefaultWhenNoSessionOrBrowser(): void {
		$i18n = $this->makeI18n('en,de');
		$this->assertSame('en', $i18n->getCurrentLanguage());
	}

	public function testGetCurrentLanguageReturnsSessionLanguage(): void {
		$_SESSION[LANG_SESSION_PARAM] = 'de';
		$i18n = $this->makeI18n('en,de');
		$this->assertSame('de', $i18n->getCurrentLanguage());
	}

	public function testGetCurrentLanguageFallsBackToDefaultWhenSessionLangUnsupported(): void {
		$_SESSION[LANG_SESSION_PARAM] = 'xx';
		$i18n = $this->makeI18n('en,de');
		$this->assertSame('en', $i18n->getCurrentLanguage());
	}

	public function testGetCurrentLanguageReturnsBrowserLanguage(): void {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9,en;q=0.8';
		$i18n = $this->makeI18n('en,de');
		$this->assertSame('de', $i18n->getCurrentLanguage());
	}

	public function testGetCurrentLanguageFallsBackToDefaultWhenBrowserLangUnsupported(): void {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
		$i18n = $this->makeI18n('en,de');
		$this->assertSame('en', $i18n->getCurrentLanguage());
	}

	public function testGetCurrentLanguageCachesResult(): void {
		$i18n = $this->makeI18n('en,de');
		$this->assertSame('en', $i18n->getCurrentLanguage());
		// Change session after first call - cached value should persist.
		$_SESSION[LANG_SESSION_PARAM] = 'de';
		$this->assertSame('en', $i18n->getCurrentLanguage());
	}

	public function testSetCurrentLanguageSetsSessionAndProperty(): void {
		$i18n = $this->makeI18n('en,de');
		$i18n->setCurrentLanguage('de');
		$this->assertSame('de', $_SESSION[LANG_SESSION_PARAM]);
		$this->assertSame('de', $i18n->getCurrentLanguage());
	}

	public function testSetCurrentLanguageLowercasesInput(): void {
		$i18n = $this->makeI18n('en,de');
		$i18n->setCurrentLanguage('DE');
		$this->assertSame('de', $_SESSION[LANG_SESSION_PARAM]);
	}

	public function testSetCurrentLanguageFallsBackWhenUnsupported(): void {
		$i18n = $this->makeI18n('en,de');
		// getCurrentLanguage() returns 'en' by default.
		$i18n->setCurrentLanguage('fr');
		$this->assertSame('en', $_SESSION[LANG_SESSION_PARAM]);
	}

	public function testSetCurrentLanguageNoOpWhenSameLanguage(): void {
		$i18n = $this->makeI18n('en,de');
		$i18n->setCurrentLanguage('de');
		// Setting again to 'de' should be a no-op (early return).
		$i18n->setCurrentLanguage('de');
		$this->assertSame('de', $_SESSION[LANG_SESSION_PARAM]);
	}

	public function testGetMessageReturnsExistingKey(): void {
		$GLOBALS['msg'] = ['hello' => 'Hello World'];
		$i18n = $this->makeI18n('en');
		$this->assertSame('Hello World', $i18n->getMessage('hello'));
	}

	public function testGetMessageReturnsPlaceholderForMissingKey(): void {
		$i18n = $this->makeI18n('en');
		$this->assertSame('???missing???', $i18n->getMessage('missing'));
	}

	public function testGetMessageWithSprintfParams(): void {
		$GLOBALS['msg'] = ['greeting' => 'Hello %s'];
		$i18n = $this->makeI18n('en');
		$this->assertSame('Hello John', $i18n->getMessage('greeting', 'John'));
	}

	public function testHasMessageReturnsTrueForExistingKey(): void {
		$GLOBALS['msg'] = ['foo' => 'bar'];
		$i18n = $this->makeI18n('en');
		$this->assertTrue($i18n->hasMessage('foo'));
	}

	public function testHasMessageReturnsFalseForMissingKey(): void {
		$i18n = $this->makeI18n('en');
		$this->assertFalse($i18n->hasMessage('nope'));
	}

	public function testGetNavigationLabelReturnsMessageWithSuffix(): void {
		$GLOBALS['msg'] = ['home_navlabel' => 'Home Page'];
		$i18n = $this->makeI18n('en');
		$this->assertSame('Home Page', $i18n->getNavigationLabel('home'));
	}

	public function testGetNavigationLabelReturnsPlaceholderForMissing(): void {
		$i18n = $this->makeI18n('en');
		$this->assertSame('???home_navlabel???', $i18n->getNavigationLabel('home'));
	}
}
