<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for PaymentSubjectMessageConverter.
 */
final class PaymentSubjectMessageConverterTest extends TestCaseBase {
	private string $cacheFile = '';

	protected function tearDown(): void {
		if ($this->cacheFile !== '' && file_exists($this->cacheFile)) {
			unlink($this->cacheFile);
		}
		parent::tearDown();
	}

	private function makeConverter(array $msgMap = []): PaymentSubjectMessageConverter {
		$i18n = $this->mockI18n();
		$i18n->method('getCurrentLanguage')->willReturn('unittest');
		$this->cacheFile = sprintf(CONFIGCACHE_MESSAGES, 'unittest');
		file_put_contents($this->cacheFile, '<?php $msg = ' . var_export($msgMap, true) . ';');
		return new PaymentSubjectMessageConverter($i18n, $this->mockWebsoccer());
	}

	public function testToHtmlReturnsTranslatedMessageWhenKeyExists(): void {
		$c = $this->makeConverter(['payment_subject_transfer' => 'Transfer Fee']);
		$html = $c->toHtml(['entity_transaction_verwendung' => 'payment_subject_transfer']);
		$this->assertSame('Transfer Fee', $html);
	}

	public function testToHtmlReturnsRawValueWhenKeyDoesNotExist(): void {
		$c = $this->makeConverter([]);
		$html = $c->toHtml(['entity_transaction_verwendung' => 'unknown_subject']);
		$this->assertSame('unknown_subject', $html);
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = $this->makeConverter([]);
		$this->assertSame('raw', $c->toText('raw'));
	}

	public function testToDbValueReturnsValueUnchanged(): void {
		$c = $this->makeConverter([]);
		$this->assertSame('raw', $c->toDbValue('raw'));
	}
}
