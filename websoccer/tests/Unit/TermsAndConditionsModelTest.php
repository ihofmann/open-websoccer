<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TermsAndConditionsModel.
 */
final class TermsAndConditionsModelTest extends TestCaseBase {
	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new TermsAndConditionsModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenLanguageNotAvailable(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n(['termsandconditions_err_notavilable' => 'not available']);
		$i18n->method('getCurrentLanguage')->willReturn('xx');
		$model = new TermsAndConditionsModel($this->mockDb(), $i18n, $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsTermsForEnglishLanguage(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$i18n->method('getCurrentLanguage')->willReturn('en');
		$model = new TermsAndConditionsModel($this->mockDb(), $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertStringContainsString('Game Membership', $params['terms']);
	}
}
