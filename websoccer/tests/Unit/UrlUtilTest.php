<?php
use OpenWebSoccer\Tests\TestCaseBase;

// The escapeOutput() function lives in admin/functions.inc.php which is not
// loaded by the test bootstrap. Provide a compatible definition so UrlUtil
// can be tested in isolation.
if (!function_exists('escapeOutput')) {
	function escapeOutput($message) {
		return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
	}
}

/**
 * Unit tests for UrlUtil.
 */
final class UrlUtilTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		$_SERVER['PHP_SELF'] = '/admin/index.php';
	}

	public function testBuildCurrentUrlWithSingleParameter(): void {
		$_GET = [];
		$url = UrlUtil::buildCurrentUrlWithParameters(['page' => 'teams']);
		$this->assertSame('/admin/index.php?page=teams', $url);
	}

	public function testBuildCurrentUrlWithMultipleParameters(): void {
		$_GET = [];
		$url = UrlUtil::buildCurrentUrlWithParameters(['page' => 'teams', 'id' => '5']);
		// escapeOutput converts & to &amp;
		$this->assertSame('/admin/index.php?page=teams&amp;id=5', $url);
	}

	public function testBuildCurrentUrlWithNoParameters(): void {
		$_GET = [];
		$url = UrlUtil::buildCurrentUrlWithParameters([]);
		// No parameters and no $_GET entries => trailing '?' with nothing after.
		$this->assertSame('/admin/index.php?', $url);
	}

	public function testBuildCurrentUrlAppendsExistingGetParams(): void {
		$_GET = ['foo' => 'bar'];
		$url = UrlUtil::buildCurrentUrlWithParameters(['page' => 'teams']);
		$this->assertSame('/admin/index.php?page=teams&amp;foo=bar', $url);
	}

	public function testBuildCurrentUrlOverridesExistingGetParam(): void {
		$_GET = ['page' => 'old'];
		$url = UrlUtil::buildCurrentUrlWithParameters(['page' => 'new']);
		// The overridden value from $parameters takes precedence; the $_GET
		// 'page' entry is skipped.
		$this->assertSame('/admin/index.php?page=new', $url);
	}

	public function testBuildCurrentUrlEscapesSpecialCharacters(): void {
		$_GET = [];
		$url = UrlUtil::buildCurrentUrlWithParameters(['q' => 'a&b']);
		// escapeOutput uses htmlspecialchars so & becomes &amp;
		$this->assertSame('/admin/index.php?q=a&amp;b', $url);
	}
}
