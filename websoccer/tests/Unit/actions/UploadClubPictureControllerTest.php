<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for UploadClubPictureController.
 */
final class UploadClubPictureControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	protected function setUp(): void {
		parent::setUp();
		$_FILES = [];
		$_POST = [];
	}

	public function testThrowsWhenFeatureDisabled(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'upload_clublogo_max_size' => 0]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('feature is not enabled.');

		$controller = new UploadClubPictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}

	public function testThrowsWhenUserHasNoClub(): void {
		$i18n = $this->mockI18n([]);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'upload_clublogo_max_size' => 100]);
		// Guest -> getClubId() (no args) returns null.
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('requires team');

		$controller = new UploadClubPictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}

	public function testThrowsForIllegalFileType(): void {
		$i18n = $this->mockI18n(['change-profile-picture_err_illegalfiletype' => 'bad type']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'upload_clublogo_max_size' => 100]);
		$ws->method('getUser')->willReturn($this->makeLoggedUser(1, 1));
		$db = $this->mockDb();
		$_FILES['picture'] = ['error' => UPLOAD_ERR_OK, 'name' => 'script.txt', 'tmp_name' => '/tmp/x', 'size' => 10];

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('bad type');

		$controller = new UploadClubPictureController($i18n, $ws, $db);
		$controller->executeAction([]);
	}
}
