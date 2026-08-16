<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for BreadcrumbBuilder.
 */
final class BreadcrumbBuilderTest extends TestCaseBase {
	/**
	 * Creates an I18n mock with getNavigationLabel backed by a map.
	 */
	private function mockI18nWithLabels(array $labels): \I18n {
		$i18n = $this->mockI18n();
		$i18n->method('getNavigationLabel')->willReturnCallback(function ($pageId) use ($labels) {
			return $labels[$pageId] ?? '???' . $pageId . '???';
		});
		return $i18n;
	}

	private function pageConfig(?string $parentItem = null): string {
		$config = [];
		if ($parentItem !== null) {
			$config['parentItem'] = $parentItem;
		}
		return json_encode($config);
	}

	public function testReturnsNullWhenPageIdNotFound(): void {
		$ws = $this->mockWebsoccer();
		$i18n = $this->mockI18nWithLabels([]);
		$result = BreadcrumbBuilder::getBreadcrumbItems($ws, $i18n, [], 'nonexistent');
		$this->assertNull($result);
	}

	public function testReturnsSingleItemForPageWithoutParent(): void {
		$pages = ['home' => $this->pageConfig()];
		$i18n = $this->mockI18nWithLabels(['home' => 'Home']);
		$ws = $this->mockWebsoccer();
		$result = BreadcrumbBuilder::getBreadcrumbItems($ws, $i18n, $pages, 'home');
		$this->assertCount(1, $result);
		$this->assertSame('Home', $result['home']);
	}

	public function testReturnsHierarchyFromChildToParent(): void {
		$pages = [
			'home' => $this->pageConfig(),
			'leagues' => $this->pageConfig('home'),
			'team' => $this->pageConfig('leagues'),
		];
		$i18n = $this->mockI18nWithLabels([
			'home' => 'Home',
			'leagues' => 'Leagues',
			'team' => 'Team',
		]);
		$ws = $this->mockWebsoccer();
		$result = BreadcrumbBuilder::getBreadcrumbItems($ws, $i18n, $pages, 'team');
		// array_reverse: parent first, child last.
		$keys = array_keys($result);
		$this->assertSame(['home', 'leagues', 'team'], $keys);
		$this->assertSame('Home', $result['home']);
		$this->assertSame('Leagues', $result['leagues']);
		$this->assertSame('Team', $result['team']);
	}

	public function testReturnsTwoLevelHierarchy(): void {
		$pages = [
			'parent' => $this->pageConfig(),
			'child' => $this->pageConfig('parent'),
		];
		$i18n = $this->mockI18nWithLabels([
			'parent' => 'Parent',
			'child' => 'Child',
		]);
		$ws = $this->mockWebsoccer();
		$result = BreadcrumbBuilder::getBreadcrumbItems($ws, $i18n, $pages, 'child');
		$keys = array_keys($result);
		$this->assertSame(['parent', 'child'], $keys);
	}
}
