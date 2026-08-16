<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ImprintModel.
 */
final class ImprintModelTest extends TestCaseBase {
	public function testRenderViewAlwaysReturnsTrue(): void {
		$ws = $this->mockWebsoccer([]);
		$model = new ImprintModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersReturnsImprintContentKey(): void {
		$ws = $this->mockWebsoccer([]);
		$model = new ImprintModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('imprint_content', $params);
		$this->assertIsString($params['imprint_content']);
	}

	public function testGetTemplateParametersReturnsEmptyStringWhenImprintFileMissing(): void {
		$ws = $this->mockWebsoccer([]);
		$model = new ImprintModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		// IMPRINT_FILE points to generated/imprint.php which does not exist in test env.
		if (!file_exists(IMPRINT_FILE)) {
			$this->assertSame('', $params['imprint_content']);
		} else {
			$this->assertIsString($params['imprint_content']);
		}
	}
}
