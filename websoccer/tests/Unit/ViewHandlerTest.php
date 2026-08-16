<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ViewHandler.
 *
 * Only the side-effect-free paths are exercised (null page, unknown page,
 * unknown block, role-based suppression). The full template-rendering path
 * requires a live Twig engine and is intentionally excluded.
 */
final class ViewHandlerTest extends TestCaseBase {
	private function makeViewHandler(array $pages = [], array $blocks = [], ?\User $user = null, ?\I18n $i18n = null): \ViewHandler {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getUser')->willReturn($user ?? $this->makeUser([]));
		$db = $this->createMock(\DbConnection::class);
		$i18n = $i18n ?? $this->mockI18n([MSG_KEY_ERROR_PAGENOTFOUND => 'page not found']);
		return new \ViewHandler($ws, $db, $i18n, $pages, $blocks);
	}

	public function testSortByWeightOrdersByWeightAscending(): void {
		$a = ['weight' => 5];
		$b = ['weight' => 1];
		$this->assertGreaterThan(0, \ViewHandler::sortByWeight($a, $b));
	}

	public function testSortByWeightReturnsNegativeForReversed(): void {
		$a = ['weight' => 1];
		$b = ['weight' => 5];
		$this->assertLessThan(0, \ViewHandler::sortByWeight($a, $b));
	}

	public function testSortByWeightReturnsZeroForEqualWeights(): void {
		$a = ['weight' => 3];
		$b = ['weight' => 3];
		$this->assertSame(0, \ViewHandler::sortByWeight($a, $b));
	}

	public function testSortByWeightReturnsZeroWhenNoWeight(): void {
		$a = [];
		$b = [];
		$this->assertSame(0, \ViewHandler::sortByWeight($a, $b));
	}

	public function testHandlePageReturnsNullForNullPageId(): void {
		$vh = $this->makeViewHandler();
		$this->assertNull($vh->handlePage(null, []));
	}

	public function testHandlePageThrowsForUnknownPage(): void {
		$vh = $this->makeViewHandler([]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('page not found');
		$vh->handlePage('no_such_page', []);
	}

	public function testRenderBlockReturnsEmptyStringForUnknownBlock(): void {
		$vh = $this->makeViewHandler([], []);
		$this->assertSame('', $vh->renderBlock('no_such_block'));
	}

	public function testRenderBlockReturnsEmptyStringWhenRoleNotAllowed(): void {
		$user = $this->makeUser([]); // guest
		$vh = $this->makeViewHandler([], [], $user);
		$result = $vh->renderBlock('b1', ['role' => 'admin', 'template' => 't'], []);
		$this->assertSame('', $result);
	}

	public function testRenderBlockFromConfigReturnsEmptyStringWhenRoleNotAllowed(): void {
		$user = $this->makeUser([]); // guest
		$blocks = ['b1' => json_encode(['role' => 'admin', 'template' => 't'])];
		$vh = $this->makeViewHandler([], $blocks, $user);
		$this->assertSame('', $vh->renderBlock('b1'));
	}

	public function testRenderBlockReturnsEmptyStringForGuestWhenUserRoleRequired(): void {
		$user = $this->makeUser([]); // guest (id null)
		$vh = $this->makeViewHandler([], [], $user);
		$result = $vh->renderBlock('b1', ['role' => 'user', 'template' => 't'], []);
		$this->assertSame('', $result);
	}
}
