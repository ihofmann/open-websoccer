<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for FileWriter. Uses a temp directory (never the project cache).
 */
final class FileWriterTest extends TestCaseBase {
	private array $tempPaths = [];

	protected function tearDown(): void {
		foreach ($this->tempPaths as $path) {
			if (is_file($path)) {
				@unlink($path);
			}
		}
		$this->tempPaths = [];
		parent::tearDown();
	}

	private function tempPath(string $suffix = '.txt'): string {
		$path = realpath(sys_get_temp_dir()) . '/ows_fwtest_' . uniqid() . $suffix;
		$this->tempPaths[] = $path;
		return $path;
	}

	public function testWriteLineCreatesFileAndWritesContent(): void {
		$path = $this->tempPath();
		$fw = new FileWriter($path);
		$fw->writeLine('hello');
		$fw->close();
		$this->assertFileExists($path);
		$this->assertSame('hello' . PHP_EOL, file_get_contents($path));
	}

	public function testWriteLineAppendsMultipleLinesInTruncateMode(): void {
		$path = $this->tempPath();
		$fw = new FileWriter($path);
		$fw->writeLine('one');
		$fw->writeLine('two');
		$fw->close();
		$this->assertSame('one' . PHP_EOL . 'two' . PHP_EOL, file_get_contents($path));
	}

	public function testTruncateModeResetsExistingFile(): void {
		$path = $this->tempPath();
		file_put_contents($path, 'old content');
		$fw = new FileWriter($path, true);
		$fw->writeLine('new');
		$fw->close();
		$this->assertSame('new' . PHP_EOL, file_get_contents($path));
	}

	public function testAppendModeKeepsExistingContent(): void {
		$path = $this->tempPath();
		file_put_contents($path, 'old' . PHP_EOL);
		$fw = new FileWriter($path, false);
		$fw->writeLine('appended');
		$fw->close();
		$this->assertSame('old' . PHP_EOL . 'appended' . PHP_EOL, file_get_contents($path));
	}

	public function testCloseIsIdempotent(): void {
		$path = $this->tempPath();
		$fw = new FileWriter($path);
		$fw->writeLine('x');
		$fw->close();
		$fw->close();
		$this->assertSame('x' . PHP_EOL, file_get_contents($path));
	}

	public function testConstructorThrowsForInvalidPath(): void {
		$invalidPath = realpath(sys_get_temp_dir()) . '/ows_nonexistent_dir_xyz_' . uniqid() . '/test.txt';
		$this->tempPaths[] = $invalidPath;
		$this->expectException(\Exception::class);
		new FileWriter($invalidPath);
	}
}
