<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for SidebarUserModel.
 */
final class SidebarUserModelTest extends TestCaseBase {
	public function testRenderViewReturnsFalseForGuest(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUser());

		$model = new SidebarUserModel($this->mockDb(), $this->mockI18n(), $ws);

		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsTeamAndUnseenCounts(): void {
		$user = $this->makeUser(['id' => 1, 'username' => 'manager']);
		$user->setClubId(7);

		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($user);

		$team = [
			'team_id' => 7,
			'team_name' => 'FC Test',
			'team_budget' => 1000,
			'team_picture' => 'test.png',
			'team_league_id' => 2,
		];

		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$team]);
		$db->method('querySelect')->willReturnCallback(
			function($columns, $fromTable) {
				if (str_ends_with($fromTable, '_briefe AS L')) {
					return $this->dbResult([['hits' => 3]]);
				}
				if (str_ends_with($fromTable, '_notification')) {
					return $this->dbResult([['hits' => 2]]);
				}
				return $this->dbResult([]);
			}
		);

		$model = new SidebarUserModel($db, $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();

		$this->assertSame($team, $params['sidebarUserteam']);
		$this->assertSame(3, $params['sidebarUnseenMessages']);
		$this->assertSame(2, $params['sidebarUnseenNotifications']);
	}
}
