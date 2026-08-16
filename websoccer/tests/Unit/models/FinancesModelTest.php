<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FinancesModel.
 */
final class FinancesModelTest extends TestCaseBase {
	private function websoccerWithUser(\User $user, array $config = [], array $request = []): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturnCallback(function ($name) use ($request) {
			return $request[$name] ?? null;
		});
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'entries_per_page' => 20]);
		$model = new FinancesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenUserHasNoClub(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'entries_per_page' => 20]);

		$model = new FinancesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsBudgetStatementsAndPaginator(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'entries_per_page' => 20]);

		$model = new FinancesModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('budget', $params);
		$this->assertArrayHasKey('statements', $params);
		$this->assertArrayHasKey('paginator', $params);
		$this->assertSame([], $params['statements']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}
}
