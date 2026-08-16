<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FormationModel.
 */
final class FormationModelTest extends TestCaseBase {
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
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'formation_max_next_matches' => 5]);
		$model = new FormationModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenNoNextMatches(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'formation_max_next_matches' => 5]);

		$model = new FormationModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->expectException(\Exception::class);
		$model->getTemplateParameters();
	}

	public function testConstructorHandlesNationalTeamRequestParameter(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->websoccerWithUser($user, ['db_prefix' => 'ws', 'formation_max_next_matches' => 5], ['nationalteam' => '1']);
		$model = new FormationModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}
}
