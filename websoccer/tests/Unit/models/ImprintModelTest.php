<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ImprintModel.
 */
final class ImprintModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$model = new ImprintModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyStringWhenPageNotFound(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$i18n->method('getCurrentLanguage')->willReturn('xx');
		$model = new ImprintModel($this->mockDb(), $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('', $params['imprint_content']);
	}

	public function testGetTemplateParametersReturnsContentForEnglishLanguage(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$i18n = $this->mockI18n();
		$i18n->method('getCurrentLanguage')->willReturn('en');
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([[
			'id' => 1,
			'type' => 'imprint',
			'language' => 'en',
			'content' => '<h2>Imprint</h2>',
		]]));
		$model = new ImprintModel($db, $i18n, $ws);
		$params = $model->getTemplateParameters();
		$this->assertStringContainsString('Imprint', $params['imprint_content']);
	}
}
