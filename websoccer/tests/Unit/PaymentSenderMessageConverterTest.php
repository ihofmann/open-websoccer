<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for PaymentSenderMessageConverter.
 */
final class PaymentSenderMessageConverterTest extends TestCaseBase {
	private string $cacheFile = '';

	protected function tearDown(): void {
		if ($this->cacheFile !== '' && file_exists($this->cacheFile)) {
			unlink($this->cacheFile);
		}
		parent::tearDown();
	}

	private function makeConverter(array $msgMap = []): PaymentSenderMessageConverter {
		$i18n = $this->mockI18n();
		$i18n->method('getCurrentLanguage')->willReturn('unittest');
		$this->cacheFile = sprintf(CONFIGCACHE_MESSAGES, 'unittest');
		file_put_contents($this->cacheFile, '<?php $msg = ' . var_export($msgMap, true) . ';');
		return new PaymentSenderMessageConverter($i18n, $this->mockWebsoccer());
	}

	public function testToHtmlReturnsTranslatedMessageWhenKeyExists(): void {
		$c = $this->makeConverter(['payment_sender_admin' => 'Administrator']);
		$html = $c->toHtml(['entity_transaction_absender' => 'payment_sender_admin']);
		$this->assertSame('Administrator', $html);
	}

	public function testToHtmlReturnsRawValueWhenKeyDoesNotExist(): void {
		$c = $this->makeConverter([]);
		$html = $c->toHtml(['entity_transaction_absender' => 'unknown_sender']);
		$this->assertSame('unknown_sender', $html);
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
