<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FileUploadHelper validation logic.
 *
 * Only the validation paths are exercised (the actual move_uploaded_file
 * step cannot run without a real HTTP upload). Temp files are cleaned up.
 */
final class FileUploadHelperTest extends TestCaseBase {
	private array $tempPaths = [];

	protected function setUp(): void {
		parent::setUp();
		$_FILES = [];
	}

	protected function tearDown(): void {
		foreach ($this->tempPaths as $path) {
			if (is_file($path)) {
				@unlink($path);
			}
		}
		$this->tempPaths = [];
		$_FILES = [];
		parent::tearDown();
	}

	private function tempFile(string $content = 'not an image'): string {
		$path = realpath(sys_get_temp_dir()) . '/ows_upload_' . uniqid() . '.dat';
		file_put_contents($path, $content);
		$this->tempPaths[] = $path;
		return $path;
	}

	public function testUploadImageFileThrowsOnInvalidExtension(): void {
		$i18n = $this->mockI18n(['validationerror_imageupload_noimagefile' => 'no image']);
		$_FILES['pic'] = [
			'name' => 'document.txt',
			'tmp_name' => '/nonexistent',
			'error' => UPLOAD_ERR_OK,
		];
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('no image');
		FileUploadHelper::uploadImageFile($i18n, 'pic', 'target', 'users');
	}

	public function testUploadImageFileThrowsOnNonImageContent(): void {
		$i18n = $this->mockI18n(['validationerror_imageupload_noimagefile' => 'no image']);
		$_FILES['pic'] = [
			'name' => 'photo.jpg',
			'tmp_name' => $this->tempFile('plain text not an image'),
			'error' => UPLOAD_ERR_OK,
		];
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('no image');
		FileUploadHelper::uploadImageFile($i18n, 'pic', 'target', 'users');
	}

	public function testUploadImageFileThrowsOnGifExtensionWithNonImageContent(): void {
		$i18n = $this->mockI18n(['validationerror_imageupload_noimagefile' => 'no image']);
		$_FILES['pic'] = [
			'name' => 'anim.gif',
			'tmp_name' => $this->tempFile('not gif'),
			'error' => UPLOAD_ERR_OK,
		];
		$this->expectException(\Exception::class);
		FileUploadHelper::uploadImageFile($i18n, 'pic', 'target', 'users');
	}

	public function testUploadImageFileThrowsOnPngExtensionWithNonImageContent(): void {
		$i18n = $this->mockI18n(['validationerror_imageupload_noimagefile' => 'no image']);
		$_FILES['pic'] = [
			'name' => 'pic.png',
			'tmp_name' => $this->tempFile('not png'),
			'error' => UPLOAD_ERR_OK,
		];
		$this->expectException(\Exception::class);
		FileUploadHelper::uploadImageFile($i18n, 'pic', 'target', 'users');
	}
}
