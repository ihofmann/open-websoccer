<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for UploadProfilePictureController.
 */
final class UploadProfilePictureControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	protected function setUp(): void {
		parent::setUp();
		$_FILES = [];
		$_POST = [];
	}

	public function testThrowsWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['user_picture_upload_enabled' => FALSE]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('feature is not enabled.');

		$controller = new UploadProfilePictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}

	public function testThrowsWhenFileSizeExceedsFormLimit(): void {
		$i18n = $this->mockI18n(['change-profile-picture_err_illegalfilesize' => 'too big']);
		$ws = $this->mockWebsoccer(['user_picture_upload_enabled' => TRUE, 'user_picture_upload_maxsize_kb' => 100]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();
		$_FILES['picture'] = ['error' => UPLOAD_ERR_FORM_SIZE, 'name' => 'x.jpg', 'tmp_name' => '/tmp/x', 'size' => 1];

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('too big');

		$controller = new UploadProfilePictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}

	public function testThrowsForIllegalFileType(): void {
		$i18n = $this->mockI18n(['change-profile-picture_err_illegalfiletype' => 'bad type']);
		$ws = $this->mockWebsoccer(['user_picture_upload_enabled' => TRUE, 'user_picture_upload_maxsize_kb' => 100]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();
		$_FILES['picture'] = ['error' => UPLOAD_ERR_OK, 'name' => 'script.txt', 'tmp_name' => '/tmp/x', 'size' => 10];

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('bad type');

		$controller = new UploadProfilePictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}
}
