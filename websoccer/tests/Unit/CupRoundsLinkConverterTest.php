<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for CupRoundsLinkConverter.
 */
final class CupRoundsLinkConverterTest extends TestCaseBase {
	public function testToHtmlGeneratesLinkWithCupIdAndRoundsName(): void {
		$i18n = $this->mockI18n(['manage_show_details' => 'Show details']);
		$c = new CupRoundsLinkConverter($i18n, $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '42', 'entity_cup_rounds' => 'Quarter Final']);
		$this->assertStringContainsString('?site=managecuprounds&cup=42', $html);
		$this->assertStringContainsString('Quarter Final', $html);
		$this->assertStringContainsString('Show details', $html);
	}

	public function testToHtmlContainsIconClass(): void {
		$i18n = $this->mockI18n(['manage_show_details' => 'Details']);
		$c = new CupRoundsLinkConverter($i18n, $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '1', 'entity_cup_rounds' => 'Final']);
		$this->assertStringContainsString('icon-tasks', $html);
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new CupRoundsLinkConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('hello', $c->toText('hello'));
	}

	public function testToDbValueReturnsValueUnchanged(): void {
		$c = new CupRoundsLinkConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('hello', $c->toDbValue('hello'));
	}
}
