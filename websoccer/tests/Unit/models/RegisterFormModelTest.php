<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for RegisterFormModel.
 */
final class RegisterFormModelTest extends TestCaseBase {
	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['allow_userregistration' => TRUE]);
		$model = new RegisterFormModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersThrowsWhenRegistrationDisabled(): void {
		$ws = $this->mockWebsoccer(['allow_userregistration' => FALSE]);
		$model = new RegisterFormModel($this->mockDb(), $this->mockI18n(['registration_disabled' => 'disabled']), $ws);
		$this->expectException(Exception::class);
		$model->getTemplateParameters();
	}

	public function testGetTemplateParametersReturnsEmptyWhenNoCaptcha(): void {
		$ws = $this->mockWebsoccer([
			'allow_userregistration' => TRUE,
			'register_use_captcha' => FALSE,
			'register_captcha_sitekey' => '',
			'register_captcha_secretkey' => ''
		]);
		$model = new RegisterFormModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertSame([], $model->getTemplateParameters());
	}

	public function testGetTemplateParametersReturnsCaptchaCodeWhenCaptchaEnabled(): void {
		$ws = $this->mockWebsoccer([
			'allow_userregistration' => TRUE,
			'register_use_captcha' => TRUE,
			'register_captcha_sitekey' => 'SITEKEY',
			'register_captcha_secretkey' => 'SECRET'
		]);
		$model = new RegisterFormModel($this->mockDb(), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('captchaCode', $params);
		$this->assertStringContainsString('SITEKEY', $params['captchaCode']);
	}
}
