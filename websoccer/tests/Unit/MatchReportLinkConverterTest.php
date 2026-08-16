<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for MatchReportLinkConverter.
 */
final class MatchReportLinkConverterTest extends TestCaseBase {
	private function msg(): array {
		return [
			'entity_match_matchreportitems' => 'Report Items',
			'match_manage_playerstatistics' => 'Player Statistics',
			'match_manage_reportitems' => 'Report Items',
			'match_manage_complete' => 'Complete Match',
		];
	}

	public function testToHtmlIncludesPlayerStatisticsAndReportItemsLinks(): void {
		$c = new MatchReportLinkConverter($this->mockI18n($this->msg()), $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '15', 'entity_match_berechnet' => '0']);
		$this->assertStringContainsString('?site=manage-match-playerstatistics&match=15', $html);
		$this->assertStringContainsString('?site=manage-match-reportitems&match=15', $html);
		$this->assertStringContainsString('Player Statistics', $html);
	}

	public function testToHtmlIncludesCompleteLinkWhenNotComputed(): void {
		$c = new MatchReportLinkConverter($this->mockI18n($this->msg()), $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '15', 'entity_match_berechnet' => '0']);
		$this->assertStringContainsString('?site=manage-match-complete&match=15', $html);
		$this->assertStringContainsString('Complete Match', $html);
	}

	public function testToHtmlOmitsCompleteLinkWhenAlreadyComputed(): void {
		$c = new MatchReportLinkConverter($this->mockI18n($this->msg()), $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '15', 'entity_match_berechnet' => '1']);
		$this->assertStringNotContainsString('manage-match-complete', $html);
	}

	public function testToHtmlContainsDropdownStructure(): void {
		$c = new MatchReportLinkConverter($this->mockI18n($this->msg()), $this->mockWebsoccer());
		$html = $c->toHtml(['id' => '15', 'entity_match_berechnet' => '0']);
		$this->assertStringContainsString('dropdown-menu', $html);
		$this->assertStringContainsString('btn-group', $html);
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new MatchReportLinkConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('hello', $c->toText('hello'));
	}

	public function testToDbValueReturnsValueUnchanged(): void {
		$c = new MatchReportLinkConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('hello', $c->toDbValue('hello'));
	}
}
