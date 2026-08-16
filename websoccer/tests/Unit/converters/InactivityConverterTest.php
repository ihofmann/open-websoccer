<?php
use OpenWebSoccer\Tests\TestCaseBase;

if (!function_exists('escapeOutput')) {
	function escapeOutput($message) {
		return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
	}
}

/**
 * Unit tests for InactivityConverter.
 */
final class InactivityConverterTest extends TestCaseBase {
	private function fullMessages(): array {
		return [
			'manage_show_details' => 'Show details',
			'button_close' => 'Close',
			'entity_user_inactivity' => 'Inactivity',
			'popup_user_inactivity_title_action' => 'Action',
			'popup_user_inactivity_total' => 'Total',
			'entity_user_inactivity_login' => 'Login',
			'entity_user_inactivity_aufstellung' => 'Lineup',
			'entity_user_inactivity_transfer' => 'Transfer',
			'entity_user_inactivity_vertragsauslauf' => 'Contract',
			'entity_users_lastonline' => 'Last online',
			'entity_users_nick' => 'Nick',
			'entity_user_inactivity_transfer_check' => 'Checked at %s',
		];
	}

	private function fullRow(): array {
		return [
			'id' => '10',
			'entity_user_inactivity' => '25',
			'entity_user_inactivity_login' => '30',
			'entity_user_inactivity_aufstellung' => '20',
			'entity_user_inactivity_transfer' => '15',
			'entity_user_inactivity_vertragsauslauf' => '5',
			'entity_user_inactivity_transfer_check' => 1700000000,
			'entity_users_nick' => 'TestUser',
			'entity_users_lastonline' => 1700000000,
		];
	}

	public function testToHtmlGeneratesModalLinkWithRate(): void {
		$c = new InactivityConverter($this->mockI18n($this->fullMessages()), $this->mockWebsoccer());
		$html = $c->toHtml($this->fullRow());
		$this->assertStringContainsString('actPopup10', $html);
		$this->assertStringContainsString('25 %', $html);
		$this->assertStringContainsString('Show details', $html);
	}

	public function testToHtmlUsesGreenColorForLowRate(): void {
		$c = new InactivityConverter($this->mockI18n($this->fullMessages()), $this->mockWebsoccer());
		$row = $this->fullRow();
		$row['entity_user_inactivity'] = '5';
		$html = $c->toHtml($row);
		$this->assertStringContainsString('color: green', $html);
	}

	public function testToHtmlUsesRedColorForHighRate(): void {
		$c = new InactivityConverter($this->mockI18n($this->fullMessages()), $this->mockWebsoccer());
		$row = $this->fullRow();
		$row['entity_user_inactivity'] = '80';
		$html = $c->toHtml($row);
		$this->assertStringContainsString('color: red', $html);
	}

	public function testToHtmlContainsPopupDiv(): void {
		$c = new InactivityConverter($this->mockI18n($this->fullMessages()), $this->mockWebsoccer());
		$html = $c->toHtml($this->fullRow());
		$this->assertStringContainsString('modal fade', $html);
		$this->assertStringContainsString('modal-footer', $html);
	}

	public function testToHtmlClampsRateAbove100(): void {
		$c = new InactivityConverter($this->mockI18n($this->fullMessages()), $this->mockWebsoccer());
		$row = $this->fullRow();
		$row['entity_user_inactivity'] = '150';
		$html = $c->toHtml($row);
		$this->assertStringContainsString('100 %', $html);
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new InactivityConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('value', $c->toText('value'));
	}

	public function testToDbValueReturnsValueUnchanged(): void {
		$c = new InactivityConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('value', $c->toDbValue('value'));
	}
}
