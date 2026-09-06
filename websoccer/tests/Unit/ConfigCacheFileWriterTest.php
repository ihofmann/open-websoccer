<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ConfigCacheFileWriter.
 *
 * The constructor opens (truncating) the real cache files, so the instance is
 * created via newInstanceWithoutConstructor() to avoid any filesystem side
 * effects. Only the pure, in-memory config-line-building helpers are tested.
 */
final class ConfigCacheFileWriterTest extends TestCaseBase {
	private \ConfigCacheFileWriter $writer;

	protected function setUp(): void {
		parent::setUp();
		$ref = new \ReflectionClass(\ConfigCacheFileWriter::class);
		$this->writer = $ref->newInstanceWithoutConstructor();
		// The destructor iterates _supportedLanguages; initialize it to avoid
		// a "foreach on null" warning since the constructor never ran.
		$langProp = new \ReflectionProperty(\ConfigCacheFileWriter::class, '_supportedLanguages');
		$langProp->setValue($this->writer, []);
	}

	private function invokePrivate(string $name, array $args) {
		$method = new \ReflectionMethod(\ConfigCacheFileWriter::class, $name);
		return $method->invokeArgs($this->writer, $args);
	}

	public function testBuildConfigLineForPage(): void {
		$doc = new \DOMDocument();
		$el = $doc->createElement('page');
		$el->setAttribute('id', 'home');
		$el->setAttribute('template', 'home');
		$el->setAttribute('navmenukey', 'top');
		$doc->appendChild($el);

		$line = $this->invokePrivate('_buildConfigLine', ['page', 'id', $el, 'core']);
		$this->assertStringStartsWith('$page[\'home\']', $line);
		$this->assertStringContainsString('navmenukey', $line);
		$this->assertStringContainsString('top', $line);
		$this->assertStringContainsString('module', $line);
		$this->assertStringEndsWith(';', $line);
	}

	public function testBuildConfigLineIncludesAttributesAndModule(): void {
		$doc = new \DOMDocument();
		$el = $doc->createElement('action');
		$el->setAttribute('id', 'login');
		$el->setAttribute('role', 'guest');
		$doc->appendChild($el);

		$line = $this->invokePrivate('_buildConfigLine', ['action', 'id', $el, 'users']);
		$this->assertStringStartsWith('$action[\'login\']', $line);
		$this->assertStringContainsString('users', $line);
		$this->assertStringContainsString('guest', $line);
	}

	public function testBuildConfigLineEscapesApostrophesInGeneratedPhpString(): void {
		$doc = new \DOMDocument();
		$el = $doc->createElement('setting');
		$el->setAttribute('id', 'csp_header');
		$el->setAttribute('default', "default-src 'none'; script-src 'self';");
		$doc->appendChild($el);

		$line = $this->invokePrivate('_buildConfigLine', ['setting', 'id', $el, 'core']);

		$this->assertStringContainsString('\u0027none\u0027', $line);
		$this->assertStringNotContainsString("default-src 'none'", $line);

		$json = substr($line, strpos($line, " = '") + 4, -2);
		$this->assertSame(
			"default-src 'none'; script-src 'self';",
			json_decode($json, true)['default']
		);
	}

	public function testBuildConfigLineForEventListenerUsesEventKey(): void {
		$doc = new \DOMDocument();
		$parent = $doc->createElement('eventlistener');
		$parent->setAttribute('event', 'UserRegistered');
		$doc->appendChild($parent);

		$line = $this->invokePrivate('_buildConfigLine', ['eventlistener', 'id', $parent, 'core']);
		$this->assertStringStartsWith('$eventlistener[\'UserRegistered\'][]', $line);
	}

	public function testBuildConfigLineIncludesChildrenIds(): void {
		$doc = new \DOMDocument();
		$root = $doc->createElement('page');
		$root->setAttribute('id', 'parent');
		$child1 = $doc->createElement('page');
		$child1->setAttribute('id', 'child1');
		$child2 = $doc->createElement('page');
		$child2->setAttribute('id', 'child2');
		$root->appendChild($child1);
		$root->appendChild($child2);
		$doc->appendChild($root);

		$line = $this->invokePrivate('_buildConfigLine', ['page', 'id', $root, 'core']);
		$this->assertStringContainsString('child1', $line);
		$this->assertStringContainsString('child2', $line);
	}

	public function testGetInnerHtmlReturnsChildXml(): void {
		$doc = new \DOMDocument();
		$root = $doc->createElement('message');
		$bold = $doc->createElement('b', 'bold text');
		$root->appendChild($bold);

		$html = $this->invokePrivate('_getInnerHtml', [$root]);
		$this->assertStringContainsString('<b>', $html);
		$this->assertStringContainsString('bold text', $html);
	}

	public function testGetInnerHtmlReturnsEmptyStringForNoChildren(): void {
		$doc = new \DOMDocument();
		$root = $doc->createElement('message');
		$doc->appendChild($root);

		$html = $this->invokePrivate('_getInnerHtml', [$root]);
		$this->assertSame('', $html);
	}
}
