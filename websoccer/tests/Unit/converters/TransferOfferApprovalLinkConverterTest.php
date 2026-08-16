<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for TransferOfferApprovalLinkConverter.
 */
final class TransferOfferApprovalLinkConverterTest extends TestCaseBase {
	public function testToHtmlGeneratesApprovalLinkWhenPending(): void {
		$i18n = $this->mockI18n(['button_approve' => 'Approve']);
		$c = new TransferOfferApprovalLinkConverter($i18n, $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '12', 'entity_transfer_offer_admin_approval_pending' => '1']);
		$this->assertStringContainsString('?site=manage&entity=transfer_offer&action=transferofferapprove&id=12', $html);
		$this->assertStringContainsString('Approve', $html);
		$this->assertStringContainsString('btn-success', $html);
	}

	public function testToHtmlShowsIconWhenNotPending(): void {
		$i18n = $this->mockI18n(['button_approve' => 'Approve']);
		$c = new TransferOfferApprovalLinkConverter($i18n, $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '12', 'entity_transfer_offer_admin_approval_pending' => '0']);
		$this->assertStringContainsString('bi-slash-circle', $html);
		$this->assertStringNotContainsString('transferofferapprove', $html);
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new TransferOfferApprovalLinkConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('hello', $c->toText('hello'));
	}

	public function testToDbValueReturnsValueUnchanged(): void {
		$c = new TransferOfferApprovalLinkConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('hello', $c->toDbValue('hello'));
	}
}
