<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ConfigFileWriter.
 *
 * The constructor and getInstance() are side-effect-free (they only store
 * settings). saveSettings() writes to the real GLOBAL_CONFIG_FILE (a generated
 * file), so it is intentionally excluded to avoid touching the project's
 * generated folder.
 */
final class ConfigFileWriterTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		$this->resetSingleton();
	}

	protected function tearDown(): void {
		$this->resetSingleton();
		parent::tearDown();
	}

	private function resetSingleton(): void {
		$ref = new \ReflectionProperty(\ConfigFileWriter::class, '_instance');
		$ref->setValue(null, null);
	}

	public function testGetInstanceReturnsSingleton(): void {
		$a = \ConfigFileWriter::getInstance(['a' => '1']);
		$b = \ConfigFileWriter::getInstance(['a' => '1']);
		$this->assertSame($a, $b);
	}

	public function testGetInstanceCreatesOnlyOneInstance(): void {
		$a = \ConfigFileWriter::getInstance(['x' => 'y']);
		// Second call with different settings still returns the same instance.
		$b = \ConfigFileWriter::getInstance(['z' => 'w']);
		$this->assertSame($a, $b);
	}

	public function testGetInstanceStoresSettings(): void {
		\ConfigFileWriter::getInstance(['foo' => 'bar']);
		$ref = new \ReflectionProperty(\ConfigFileWriter::class, '_settings');
		$instance = $this->readInstance();
		$this->assertSame(['foo' => 'bar'], $ref->getValue($instance));
	}

	private function readInstance(): \ConfigFileWriter {
		$ref = new \ReflectionProperty(\ConfigFileWriter::class, '_instance');
		return $ref->getValue(null);
	}
}
