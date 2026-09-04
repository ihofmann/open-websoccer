<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for NavigationItem.
 */
final class NavigationItemTest extends TestCaseBase {
	public function testConstructorSetsAllProperties(): void {
		$child = new NavigationItem('child', 'Child', [], false, 1);
		$item = new NavigationItem('home', 'Home', [$child], true, 5);
		$this->assertSame('home', $item->pageId);
		$this->assertSame('Home', $item->label);
		$this->assertCount(1, $item->children);
		$this->assertSame($child, $item->children[0]);
		$this->assertTrue($item->isActive);
		$this->assertSame(5, $item->weight);
		$this->assertNull($item->navMenuKey);
	}

	public function testConstructorSetsMenuKey(): void {
		$item = new NavigationItem('home', 'Home', [], false, 0, 'top');
		$this->assertSame('top', $item->navMenuKey);
	}

	public function testConstructorAcceptsEmptyChildrenArray(): void {
		$item = new NavigationItem('page', 'Label', [], false, 0);
		$this->assertSame([], $item->children);
		$this->assertFalse($item->isActive);
	}

	public function testConstructorAcceptsNullChildren(): void {
		$item = new NavigationItem('page', 'Label', null, false, 0);
		$this->assertNull($item->children);
	}

	public function testConstructorAcceptsZeroWeight(): void {
		$item = new NavigationItem('p', 'L', [], false, 0);
		$this->assertSame(0, $item->weight);
	}

	public function testConstructorAcceptsNegativeWeight(): void {
		$item = new NavigationItem('p', 'L', [], false, -1);
		$this->assertSame(-1, $item->weight);
	}

	public function testPropertiesArePubliclyWritable(): void {
		$item = new NavigationItem('a', 'A', [], false, 0);
		$item->pageId = 'b';
		$item->label = 'B';
		$item->isActive = true;
		$item->weight = 10;
		$this->assertSame('b', $item->pageId);
		$this->assertSame('B', $item->label);
		$this->assertTrue($item->isActive);
		$this->assertSame(10, $item->weight);
	}
}
