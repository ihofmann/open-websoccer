<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for BadgesModel.
 */
final class BadgesModelTest extends TestCaseBase {
	private function dbMock(array $selectMap = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where, $params = null, $limit = null) use ($selectMap) {
			foreach ($selectMap as $needle => $rows) {
				if (strpos($fromTable, $needle) !== false) {
					return $this->dbResult($rows);
				}
			}
			return $this->dbResult([]);
		});
		return $db;
	}

	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new BadgesModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyBadgesWhenNoRows(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new BadgesModel($this->dbMock(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('badges', $params);
		$this->assertSame([], $params['badges']);
	}

	public function testGetTemplateParametersReturnsBadgesFromDb(): void {
		$rows = [
			['id' => 1, 'event' => 'win', 'level' => 1, 'name' => 'badge_win_1'],
			['id' => 2, 'event' => 'win', 'level' => 2, 'name' => 'badge_win_2'],
		];
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new BadgesModel($this->dbMock(['_badge' => $rows]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(2, $params['badges']);
		$this->assertSame($rows, $params['badges']);
	}
}
