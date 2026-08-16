<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UserClubsSelectionModel.
 */
final class UserClubsSelectionModelTest extends TestCaseBase {
	private function ws(array $config, \User $user): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsFalseForGuest(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], $this->makeUser(['username' => '']));
		$model = new UserClubsSelectionModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsTrueForLoggedInUser(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], $this->makeUser(['id' => 1, 'username' => 'foo']));
		$model = new UserClubsSelectionModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyTeamsWhenNoClubs(): void {
		$ws = $this->ws(['db_prefix' => 'ws'], $this->makeUser(['id' => 1, 'username' => 'foo']));
		$model = new UserClubsSelectionModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('userteams', $params);
		$this->assertSame([], $params['userteams']);
	}

	public function testGetTemplateParametersReturnsTeamsFromDb(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['id' => 10, 'name' => 'FC Test'],
			['id' => 11, 'name' => 'United'],
		]));
		$ws = $this->ws(['db_prefix' => 'ws'], $this->makeUser(['id' => 1, 'username' => 'foo']));
		$model = new UserClubsSelectionModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['userteams']);
		$this->assertSame('FC Test', $params['userteams'][0]['name']);
	}
}
