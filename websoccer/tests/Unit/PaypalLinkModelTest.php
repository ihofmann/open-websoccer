<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PaypalLinkModel.
 */
final class PaypalLinkModelTest extends TestCaseBase {
	public function testRenderViewReturnsTrueWhenEnabled(): void {
		$ws = $this->mockWebsoccer(['paypal_enabled' => TRUE, 'db_prefix' => 'ws', 'paypal_buttonhtml' => '<form></form>']);
		$model = new PaypalLinkModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenDisabled(): void {
		$ws = $this->mockWebsoccer(['paypal_enabled' => FALSE, 'db_prefix' => 'ws', 'paypal_buttonhtml' => '']);
		$model = new PaypalLinkModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersInjectsUserIdIntoButtonHtml(): void {
		$ws = $this->mockWebsoccer(['paypal_enabled' => TRUE, 'db_prefix' => 'ws', 'paypal_buttonhtml' => '<form action="x"></form>']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 42]));
		$ws->method('getInternalActionUrl')->willReturn('http://notify.url');
		$model = new PaypalLinkModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertStringContainsString('name="custom" value="42"', $params['linkCode']);
		$this->assertStringContainsString('name="notify_url"', $params['linkCode']);
	}
}
