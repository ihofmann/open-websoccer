<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for DeleteProfilePictureController.
 */
final class DeleteProfilePictureControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'user_picture_upload_enabled' => FALSE,
		]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryUpdate');

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('feature is not enabled.');

		$controller = new DeleteProfilePictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}

	public function testClearsPictureAndReturnsUser(): void {
		$i18n = $this->mockI18n(['delete-profile-picture_success' => 'deleted']);
		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'user_picture_upload_enabled' => TRUE,
		]);
		$user = $this->makeUser(['id' => 7, 'username' => 'manager']);
		$ws->method('getUser')->willReturn($user);

		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['picture' => '']]));
		$captured = null;
		$db->expects($this->once())->method('queryUpdate')
			->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$captured) {
				$captured = [$columns, $fromTable, $whereCondition, $parameters];
			});

		$controller = new DeleteProfilePictureController($i18n, $ws, $db);
		$this->assertSame('user', $controller->executeAction([]));

		$this->assertSame('', $captured[0]['picture']);
		$this->assertSame('ws_user', $captured[1]);
		$this->assertSame('id = %d', $captured[2]);
		$this->assertSame(7, $captured[3]);
	}

	public function testUpdatesEvenWhenPictureNameSetButFileMissing(): void {
		$i18n = $this->mockI18n(['delete-profile-picture_success' => 'deleted']);
		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'user_picture_upload_enabled' => TRUE,
		]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 3]));

		$db = $this->createMock(\DbConnection::class);
		// Picture name is set but the file does not exist on disk -> no unlink, update still happens.
		$db->method('querySelect')->willReturn($this->dbResult([['picture' => 'nonexistent-pic.jpg']]));
		$db->expects($this->once())->method('queryUpdate');

		$controller = new DeleteProfilePictureController($i18n, $ws, $db);
		$this->assertSame('user', $controller->executeAction([]));
	}
}
