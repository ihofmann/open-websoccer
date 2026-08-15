<?php

class RecaptchaService {
	public static function render($siteKey) {
		return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8') . '"></div>'
				. '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
	}

	public static function verify($secretKey, $response, $remoteIp = null) {
		$recaptcha = new \ReCaptcha\ReCaptcha($secretKey);
		return $recaptcha->verify($response, $remoteIp)->isSuccess();
	}
}
