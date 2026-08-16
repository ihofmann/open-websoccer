<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for NotificationsDataService.
 */
final class NotificationsDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(['db_prefix' => 'ws', 'context_root' => '/ws']);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testCreateNotificationInsertsBasicColumns(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$captured = [];
		$capturedTable = '';
		$db->expects($this->once())->method('queryInsert')->willReturnCallback(function ($columns, $table) use (&$captured, &$capturedTable) {
			$captured = $columns;
			$capturedTable = $table;
			return 1;
		});
		NotificationsDataService::createNotification($ws, $db, 7, 'absence_notification');
		$this->assertSame('ws_notification', $capturedTable);
		$this->assertSame(7, $captured['user_id']);
		$this->assertSame('absence_notification', $captured['message_key']);
		$this->assertArrayHasKey('eventdate', $captured);
		$this->assertArrayNotHasKey('message_data', $captured);
		$this->assertArrayNotHasKey('eventtype', $captured);
	}

	public function testCreateNotificationInsertsAllOptionalColumns(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$captured = [];
		$db->expects($this->once())->method('queryInsert')->willReturnCallback(function ($columns, $table) use (&$captured) {
			$captured = $columns;
			return 1;
		});
		NotificationsDataService::createNotification($ws, $db, 7, 'badge_notification',
			['until' => 200, 'user' => 'joe'], 'badge', 'user', 'id=7', 5);
		$this->assertSame('{"until":200,"user":"joe"}', $captured['message_data']);
		$this->assertSame('badge', $captured['eventtype']);
		$this->assertSame('user', $captured['target_pageid']);
		$this->assertSame('id=7', $captured['target_querystr']);
		$this->assertSame(5, $captured['team_id']);
	}

	public function testCountUnseenNotificationsReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '3']]));
		$this->assertSame('3', NotificationsDataService::countUnseenNotifications($ws, $db, 7, 5));
	}

	public function testCountUnseenNotificationsReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, NotificationsDataService::countUnseenNotifications($ws, $db, 7, 5));
	}

	public function testGetLatestNotificationsReturnsNotificationsWithMessage(): void {
		$ws = $this->makeWebsoccer();
		$i18n = $this->mockI18n(['absence_notification' => 'User {user} is absent until {until}']);
		$db = $this->dbSelect($this->dbResult([
			['id' => '1', 'eventdate' => '100', 'eventtype' => 'absence', 'seen' => '0',
			 'message_key' => 'absence_notification', 'message_data' => json_encode(['user' => 'joe', 'until' => '200']),
			 'target_pageid' => '', 'target_querystr' => ''],
		]));
		$notifications = NotificationsDataService::getLatestNotifications($ws, $db, $i18n, 7, 5, 10);
		$this->assertCount(1, $notifications);
		$this->assertSame('User joe is absent until 200', $notifications[0]['message']);
		$this->assertSame('', $notifications[0]['link']);
	}

	public function testGetLatestNotificationsFallsBackToKeyWhenNoMessage(): void {
		$ws = $this->makeWebsoccer();
		$i18n = $this->mockI18n([]);
		$db = $this->dbSelect($this->dbResult([
			['id' => '2', 'eventdate' => '200', 'eventtype' => 'badge', 'seen' => '1',
			 'message_key' => 'badge_notification', 'message_data' => '',
			 'target_pageid' => '', 'target_querystr' => ''],
		]));
		$notifications = NotificationsDataService::getLatestNotifications($ws, $db, $i18n, 7, 5, 10);
		$this->assertSame('badge_notification', $notifications[0]['message']);
	}

	public function testGetLatestNotificationsBuildsLinkWhenTargetPageSet(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getInternalUrl')->willReturnCallback(function ($pageId, $queryString = '') {
			return '/ws/?page=' . $pageId . ($queryString ? '&' . $queryString : '');
		});
		$i18n = $this->mockI18n([]);
		$db = $this->dbSelect($this->dbResult([
			['id' => '3', 'eventdate' => '300', 'eventtype' => 'transferoffer', 'seen' => '0',
			 'message_key' => 'transferoffer_notification', 'message_data' => '',
			 'target_pageid' => 'transferoffers', 'target_querystr' => ''],
		]));
		$notifications = NotificationsDataService::getLatestNotifications($ws, $db, $i18n, 7, 5, 10);
		$this->assertSame('/ws/?page=transferoffers', $notifications[0]['link']);
	}

	public function testGetLatestNotificationsBuildsLinkWithQueryString(): void {
		$ws = $this->makeWebsoccer();
		$ws->method('getInternalUrl')->willReturnCallback(function ($pageId, $queryString = '') {
			return '/ws/?page=' . $pageId . ($queryString ? '&' . $queryString : '');
		});
		$i18n = $this->mockI18n([]);
		$db = $this->dbSelect($this->dbResult([
			['id' => '4', 'eventdate' => '400', 'eventtype' => 'transferoffer', 'seen' => '0',
			 'message_key' => 'transferoffer_notification', 'message_data' => '',
			 'target_pageid' => 'player', 'target_querystr' => 'id=10'],
		]));
		$notifications = NotificationsDataService::getLatestNotifications($ws, $db, $i18n, 7, 5, 10);
		$this->assertSame('/ws/?page=player&id=10', $notifications[0]['link']);
	}

	public function testGetLatestNotificationsReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$i18n = $this->mockI18n([]);
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], NotificationsDataService::getLatestNotifications($ws, $db, $i18n, 7, 5, 10));
	}
}
