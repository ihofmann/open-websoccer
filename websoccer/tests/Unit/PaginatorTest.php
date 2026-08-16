<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for Paginator.
 */
final class PaginatorTest extends TestCaseBase {
	/**
	 * Creates a WebSoccer mock whose getRequestParameter returns the given value.
	 * (Cannot use mockWebsoccer() because its getRequestParameter stub would
	 * shadow any later override.)
	 */
	private function mockWebsoccerWithPageNo(string|int $pageNo): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getRequestParameter')->willReturn((string) $pageNo);
		return $ws;
	}

	private function mockWebsoccerNoPageNo(): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getRequestParameter')->willReturn(null);
		return $ws;
	}

	public function testConstructorReadsPageNumberFromRequestParameter(): void {
		$ws = $this->mockWebsoccerWithPageNo(3);
		$p = new Paginator(100, 10, $ws);
		$this->assertSame(3, $p->pageNo);
		$this->assertSame(10, $p->eps);
	}

	public function testConstructorDefaultsToPageOneWhenNoPageNumberGiven(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(50, 10, $ws);
		$this->assertSame(1, $p->pageNo);
	}

	public function testConstructorDefaultsToPageOneForZeroPageNumber(): void {
		$ws = $this->mockWebsoccerWithPageNo('0');
		$p = new Paginator(50, 10, $ws);
		// max(1, 0) => 1
		$this->assertSame(1, $p->pageNo);
	}

	public function testConstructorDefaultsToPageOneForNegativePageNumber(): void {
		$ws = $this->mockWebsoccerWithPageNo('-5');
		$p = new Paginator(50, 10, $ws);
		// max(1, -5) => 1
		$this->assertSame(1, $p->pageNo);
	}

	public function testPagesCalculationWithExactDivision(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(100, 10, $ws);
		$this->assertEquals(10, $p->pages);
	}

	public function testPagesCalculationWithRemainder(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(105, 10, $ws);
		// floor(105/10) + 1 = 10 + 1 = 11 (floor returns float)
		$this->assertEquals(11, $p->pages);
	}

	public function testPagesCalculationWithFewerItemsThanEps(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(3, 10, $ws);
		// floor(3/10) + 1 = 0 + 1 = 1
		$this->assertEquals(1, $p->pages);
	}

	public function testPagesCalculationWithZeroHits(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(0, 10, $ws);
		// 0 % 10 == 0, so pages = 0 / 10 = 0
		$this->assertEquals(0, $p->pages);
	}

	public function testGetFirstIndexOnFirstPage(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(100, 10, $ws);
		$this->assertSame(0, $p->getFirstIndex());
	}

	public function testGetFirstIndexOnThirdPage(): void {
		$ws = $this->mockWebsoccerWithPageNo(3);
		$p = new Paginator(100, 10, $ws);
		// (3 - 1) * 10 = 20
		$this->assertSame(20, $p->getFirstIndex());
	}

	public function testGetQueryStringReturnsEmptyStringWhenNoParametersAdded(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(100, 10, $ws);
		$this->assertSame('', $p->getQueryString());
	}

	public function testAddParameterAppendsToQueryString(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(100, 10, $ws);
		$p->addParameter('sort', 'name');
		$p->addParameter('order', 'asc');
		$qs = $p->getQueryString();
		$this->assertStringContainsString('sort=name', $qs);
		$this->assertStringContainsString('order=asc', $qs);
	}

	public function testAddParameterOverwritesExistingParameter(): void {
		$ws = $this->mockWebsoccerNoPageNo();
		$p = new Paginator(100, 10, $ws);
		$p->addParameter('sort', 'name');
		$p->addParameter('sort', 'date');
		$qs = $p->getQueryString();
		$this->assertStringContainsString('sort=date', $qs);
		$this->assertStringNotContainsString('sort=name', $qs);
	}
}
