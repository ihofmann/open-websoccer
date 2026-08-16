<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\ActionTestHelpers;

/**
 * Unit tests for PaypalPaymentNotificationController.
 */
final class PaypalPaymentNotificationControllerTest extends TestCaseBase {
	use ActionTestHelpers;

	public function testThrowsWhenPaypalHostUnreachable(): void {
		$i18n = $this->mockI18n([]);
		// 'host.invalid' is reserved (RFC 6761) to never resolve, so fsockopen
		// fails immediately without making a real network connection.
		$ws = $this->mockWebsoccer([
			'paypal_url' => 'host.invalid',
			'paypal_host' => 'host.invalid',
			'paypal_receiver_email' => 'shop@example.com',
		]);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$db = $this->mockDb();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Error on HTTP(S) request');

		$controller = new PaypalPaymentNotificationController($i18n, $ws, $db);
		// fsockopen() emits a warning when the host does not resolve; that warning
		// is the expected failure mode here, not a test concern, so silence it.
		$prev = error_reporting(0);
		try {
			$controller->executeAction([]);
		} finally {
			error_reporting($prev);
		}
	}
}
