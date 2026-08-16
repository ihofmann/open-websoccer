<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ConverterFactory.
 */
final class ConverterFactoryTest extends TestCaseBase {
	/**
	 * Resets the static converter cache via reflection so each test starts clean.
	 */
	private function resetConverterCache(): void {
		$ref = new \ReflectionClass(\ConverterFactory::class);
		$prop = $ref->getProperty('_createdConverters');
		$prop->setValue(null, null);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->resetConverterCache();
	}

	public function testGetConverterReturnsInstanceOfRequestedConverter(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$converter = ConverterFactory::getConverter($ws, $i18n, 'PaymentSubjectMessageConverter');
		$this->assertInstanceOf(\PaymentSubjectMessageConverter::class, $converter);
		$this->assertInstanceOf(\IConverter::class, $converter);
	}

	public function testGetConverterCachesInstances(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$first = ConverterFactory::getConverter($ws, $i18n, 'PaymentSubjectMessageConverter');
		$second = ConverterFactory::getConverter($ws, $i18n, 'PaymentSubjectMessageConverter');
		$this->assertSame($first, $second);
	}

	public function testGetConverterCreatesDifferentInstancesForDifferentClasses(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$a = ConverterFactory::getConverter($ws, $i18n, 'PaymentSubjectMessageConverter');
		$b = ConverterFactory::getConverter($ws, $i18n, 'PaymentSenderMessageConverter');
		$this->assertNotSame($a, $b);
		$this->assertInstanceOf(\PaymentSenderMessageConverter::class, $b);
	}

	public function testGetConverterThrowsForNonExistentClass(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Converter not found: NonExistentConverter');
		ConverterFactory::getConverter($ws, $i18n, 'NonExistentConverter');
	}
}
