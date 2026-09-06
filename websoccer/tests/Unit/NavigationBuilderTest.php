<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for NavigationBuilder.
 */
final class NavigationBuilderTest extends TestCaseBase {
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

	/**
	 * Creates a WebSoccer mock whose getUser() returns a User with the given role.
	 */
	private function mockWebsoccerWithRole(string $role, array $config = []): \WebSoccer {
		$user = $this->makeUser([
			'id' => ($role === ROLE_USER) ? 1 : null,
			'username' => ($role === ROLE_USER) ? 'bob' : '',
		]);
		$ws = $this->mockWebsoccer($config);
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	private function navPage(string $role, bool $navitem = true, ?string $parent = null, int $weight = 0, ?string $configDep = null, ?string $navMenuKey = null): string {
		$config = [
			'role' => $role,
			'navitem' => $navitem ? 'true' : 'false',
		];
		if ($parent !== null) {
			$config['parentItem'] = $parent;
		}
		if ($weight !== 0) {
			$config['navweight'] = (string) $weight;
		}
		if ($configDep !== null) {
			$config['navitemOnlyForConfigEnabled'] = $configDep;
		}
		if ($navMenuKey !== null) {
			$config['navmenukey'] = $navMenuKey;
		}
		return json_encode($config);
	}

	public function testReturnsEmptyArrayForNoPages(): void {
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels([]);
		$this->assertSame([], NavigationBuilder::getNavigationItems($ws, $i18n, [], 'home'));
	}

	public function testReturnsItemForMatchingRole(): void {
		$pages = [
			'home' => $this->navPage('guest'),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['home' => 'Home']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertCount(1, $items);
		$this->assertSame('home', $items[0]->pageId);
		$this->assertSame('Home', $items[0]->label);
		$this->assertTrue($items[0]->isActive);
		$this->assertNull($items[0]->navMenuKey);
	}

	public function testIncludesConfiguredMenuKey(): void {
		$pages = [
			'home' => $this->navPage('guest', true, null, 0, null, 'top'),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['home' => 'Home']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertSame('top', $items[0]->navMenuKey);
	}

	public function testSkipsItemForNonMatchingRole(): void {
		$pages = [
			'admin' => $this->navPage('admin'),
			'home' => $this->navPage('guest'),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['home' => 'Home', 'admin' => 'Admin']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertCount(1, $items);
		$this->assertSame('home', $items[0]->pageId);
	}

	public function testSkipsItemWhenNavitemIsFalse(): void {
		$pages = [
			'hidden' => $this->navPage('guest', false),
			'home' => $this->navPage('guest'),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['home' => 'Home', 'hidden' => 'Hidden']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertCount(1, $items);
		$this->assertSame('home', $items[0]->pageId);
	}

	public function testSortsItemsByWeight(): void {
		$pages = [
			'heavy' => $this->navPage('guest', true, null, 10),
			'light' => $this->navPage('guest', true, null, 1),
			'mid' => $this->navPage('guest', true, null, 5),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['heavy' => 'Heavy', 'light' => 'Light', 'mid' => 'Mid']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertCount(3, $items);
		$this->assertSame('light', $items[0]->pageId);
		$this->assertSame('mid', $items[1]->pageId);
		$this->assertSame('heavy', $items[2]->pageId);
	}

	public function testCreatesParentItemBeforeChild(): void {
		$pages = [
			'child' => $this->navPage('guest', true, 'parent', 1),
			'parent' => $this->navPage('guest', true, null, 0),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['child' => 'Child', 'parent' => 'Parent']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'child');
		// Parent is a top-level item; child is nested in parent's children.
		$this->assertCount(1, $items);
		$this->assertSame('parent', $items[0]->pageId);
		$this->assertCount(1, $items[0]->children);
		$this->assertSame('child', $items[0]->children[0]->pageId);
	}

	public function testMarksParentActiveWhenChildIsActive(): void {
		$pages = [
			'child' => $this->navPage('guest', true, 'parent', 1),
			'parent' => $this->navPage('guest', true, null, 0),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST);
		$i18n = $this->mockI18nWithLabels(['child' => 'Child', 'parent' => 'Parent']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'child');
		$this->assertTrue($items[0]->isActive);
		$this->assertTrue($items[0]->children[0]->isActive);
	}

	public function testSkipsItemWhenConfigDependencyDisabled(): void {
		$pages = [
			'dep' => $this->navPage('guest', true, null, 0, 'feature_x'),
			'home' => $this->navPage('guest', true, null, 0),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST, ['feature_x' => '0']);
		$i18n = $this->mockI18nWithLabels(['dep' => 'Dep', 'home' => 'Home']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertCount(1, $items);
		$this->assertSame('home', $items[0]->pageId);
	}

	public function testShowsItemWhenConfigDependencyEnabled(): void {
		$pages = [
			'dep' => $this->navPage('guest', true, null, 0, 'feature_x'),
		];
		$ws = $this->mockWebsoccerWithRole(ROLE_GUEST, ['feature_x' => '1']);
		$i18n = $this->mockI18nWithLabels(['dep' => 'Dep']);
		$items = NavigationBuilder::getNavigationItems($ws, $i18n, $pages, 'home');
		$this->assertCount(1, $items);
		$this->assertSame('dep', $items[0]->pageId);
	}

	public function testSortByWeightComparesCorrectly(): void {
		$a = new NavigationItem('a', 'A', [], false, 5);
		$b = new NavigationItem('b', 'B', [], false, 10);
		$this->assertSame(-5, NavigationBuilder::sortByWeight($a, $b));
		$this->assertSame(5, NavigationBuilder::sortByWeight($b, $a));
		$this->assertSame(0, NavigationBuilder::sortByWeight($a, $a));
	}
}
