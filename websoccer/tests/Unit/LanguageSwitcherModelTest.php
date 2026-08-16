<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for LanguageSwitcherModel.
 */
final class LanguageSwitcherModelTest extends TestCaseBase {
	private function i18nWithLanguages(array $languages): \I18n {
		$i18n = $this->mockI18n();
		$i18n->method('getSupportedLanguages')->willReturn($languages);
		return $i18n;
	}

	public function testRenderViewReturnsTrueWhenMultipleLanguages(): void {
		$i18n = $this->i18nWithLanguages(['en' => 'English', 'de' => 'Deutsch']);
		$model = new LanguageSwitcherModel($this->mockDb(), $i18n, $this->mockWebsoccer([]));
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenSingleLanguage(): void {
		$i18n = $this->i18nWithLanguages(['en' => 'English']);
		$model = new LanguageSwitcherModel($this->mockDb(), $i18n, $this->mockWebsoccer([]));
		$this->assertFalse($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenNoLanguages(): void {
		$i18n = $this->i18nWithLanguages([]);
		$model = new LanguageSwitcherModel($this->mockDb(), $i18n, $this->mockWebsoccer([]));
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyArray(): void {
		$i18n = $this->i18nWithLanguages(['en' => 'English', 'de' => 'Deutsch']);
		$model = new LanguageSwitcherModel($this->mockDb(), $i18n, $this->mockWebsoccer([]));
		$this->assertSame([], $model->getTemplateParameters());
	}
}
