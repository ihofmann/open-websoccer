<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ForgotPasswordModel.
 */
final class ForgotPasswordModelTest extends TestCaseBase {
	public function testRenderViewReturnsTrueWhenSendingPasswordAllowed(): void {
		$ws = $this->mockWebsoccer(['login_allow_sendingpassword' => TRUE]);
		$model = new ForgotPasswordModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenSendingPasswordDisabled(): void {
		$ws = $this->mockWebsoccer(['login_allow_sendingpassword' => FALSE]);
		$model = new ForgotPasswordModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyWhenNoCaptcha(): void {
		$ws = $this->mockWebsoccer([
			'login_allow_sendingpassword' => TRUE,
			'register_use_captcha' => FALSE,
			'register_captcha_sitekey' => '',
			'register_captcha_secretkey' => '',
		]);
		$model = new ForgotPasswordModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}

	public function testGetTemplateParametersReturnsCaptchaCodeWhenCaptchaConfigured(): void {
		$ws = $this->mockWebsoccer([
			'login_allow_sendingpassword' => TRUE,
			'register_use_captcha' => TRUE,
			'register_captcha_sitekey' => 'sitekey123',
			'register_captcha_secretkey' => 'secret456',
		]);
		$model = new ForgotPasswordModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('captchaCode', $params);
		$this->assertStringContainsString('sitekey123', $params['captchaCode']);
	}
}
