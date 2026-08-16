<?php
use OpenWebSoccer\Tests\TestCaseBase;

// createFormGroup() emits HTML and calls escapeOutput(), which lives in
// admin/functions.inc.php (not loaded by the test bootstrap). Provide a
// compatible implementation so the rendering tests can run.
if (!function_exists('escapeOutput')) {
	function escapeOutput($message) {
		return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
	}
}

/**
 * Unit tests for FormBuilder.
 *
 * validateField() is pure logic (no output) and is tested directly.
 * createFormGroup() writes HTML to the output buffer, which is captured via
 * ob_start()/ob_get_clean().
 */
final class FormBuilderTest extends TestCaseBase {
	private function captureCreateFormGroup(\I18n $i18n, string $fieldId, array $fieldInfo, string $fieldValue, string $labelKeyPrefix): string {
		ob_start();
		FormBuilder::createFormGroup($i18n, $fieldId, $fieldInfo, $fieldValue, $labelKeyPrefix);
		return ob_get_clean();
	}

	public function testValidateFieldThrowsOnRequiredEmpty(): void {
		$i18n = $this->mockI18n([
			'validationerror_required' => 'required: %s',
			'lbl_name' => 'Name',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('required: Name');
		FormBuilder::validateField($i18n, 'name', ['type' => 'text', 'required' => true], '', 'lbl_');
	}

	public function testValidateFieldPassesOnRequiredProvided(): void {
		$i18n = $this->mockI18n(['lbl_name' => 'Name']);
		// Should not throw.
		FormBuilder::validateField($i18n, 'name', ['type' => 'text', 'required' => true], 'value', 'lbl_');
		$this->assertTrue(true);
	}

	public function testValidateFieldBooleanRequiredEmptyDoesNotThrow(): void {
		$i18n = $this->mockI18n(['lbl_active' => 'Active']);
		FormBuilder::validateField($i18n, 'active', ['type' => 'boolean', 'required' => true], '', 'lbl_');
		$this->assertTrue(true);
	}

	public function testValidateFieldThrowsOnTextTooLong(): void {
		$i18n = $this->mockI18n([
			'validationerror_text_too_long' => 'too long: %s',
			'lbl_name' => 'Name',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('too long: Name');
		FormBuilder::validateField($i18n, 'name', ['type' => 'text', 'required' => false], str_repeat('a', 256), 'lbl_');
	}

	public function testValidateFieldThrowsOnInvalidEmail(): void {
		$i18n = $this->mockI18n(['validationerror_email' => 'bad email']);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('bad email');
		FormBuilder::validateField($i18n, 'email', ['type' => 'email', 'required' => false], 'not-an-email', 'lbl_');
	}

	public function testValidateFieldPassesOnValidEmail(): void {
		$i18n = $this->mockI18n(['lbl_email' => 'Email']);
		FormBuilder::validateField($i18n, 'email', ['type' => 'email', 'required' => false], 'a@b.com', 'lbl_');
		$this->assertTrue(true);
	}

	public function testValidateFieldThrowsOnInvalidUrl(): void {
		$i18n = $this->mockI18n([
			'validationerror_url' => 'bad url: %s',
			'lbl_site' => 'Site',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('bad url: Site');
		FormBuilder::validateField($i18n, 'site', ['type' => 'url', 'required' => false], 'not a url', 'lbl_');
	}

	public function testValidateFieldThrowsOnNonNumericNumber(): void {
		$i18n = $this->mockI18n([
			'validationerror_number' => 'not a number: %s',
			'lbl_age' => 'Age',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not a number: Age');
		FormBuilder::validateField($i18n, 'age', ['type' => 'number', 'required' => false], 'abc', 'lbl_');
	}

	public function testValidateFieldThrowsOnNonIntegerPercent(): void {
		$i18n = $this->mockI18n([
			'validationerror_percent' => 'bad percent: %s',
			'lbl_pct' => 'Pct',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('bad percent: Pct');
		FormBuilder::validateField($i18n, 'pct', ['type' => 'percent', 'required' => false], '12.5', 'lbl_');
	}

	public function testValidateFieldPassesOnValidPercent(): void {
		$i18n = $this->mockI18n(['lbl_pct' => 'Pct']);
		FormBuilder::validateField($i18n, 'pct', ['type' => 'percent', 'required' => false], '50', 'lbl_');
		$this->assertTrue(true);
	}

	public function testCreateFormGroupBooleanCheckboxChecked(): void {
		$i18n = $this->mockI18n(['lbl_active' => 'Active']);
		$html = $this->captureCreateFormGroup($i18n, 'active', ['type' => 'boolean'], '1', 'lbl_');
		$this->assertStringContainsString('checkbox', $html);
		$this->assertStringContainsString('checked', $html);
		$this->assertStringContainsString('Active', $html);
	}

	public function testCreateFormGroupBooleanCheckboxUnchecked(): void {
		$i18n = $this->mockI18n(['lbl_active' => 'Active']);
		$html = $this->captureCreateFormGroup($i18n, 'active', ['type' => 'boolean'], '0', 'lbl_');
		$this->assertStringContainsString('checkbox', $html);
		$this->assertStringNotContainsString('checked', $html);
	}

	public function testCreateFormGroupTextInput(): void {
		$i18n = $this->mockI18n(['lbl_name' => 'Name']);
		$html = $this->captureCreateFormGroup($i18n, 'name', ['type' => 'text'], 'hello & world', 'lbl_');
		$this->assertStringContainsString('<input', $html);
		$this->assertStringContainsString('type=\'text\'', $html);
		$this->assertStringContainsString('name=\'name\'', $html);
		// value is escaped.
		$this->assertStringContainsString('hello &amp; world', $html);
	}

	public function testCreateFormGroupSelectWithSelectedOption(): void {
		$i18n = $this->mockI18n(['lbl_color' => 'Color']);
		$html = $this->captureCreateFormGroup($i18n, 'color', ['type' => 'select', 'selection' => 'red,green,blue'], 'green', 'lbl_');
		$this->assertStringContainsString('<select', $html);
		$this->assertStringContainsString('value=\'green\'', $html);
		$this->assertStringContainsString('selected', $html);
	}

	public function testCreateFormGroupMarksRequiredLabelBold(): void {
		$i18n = $this->mockI18n(['lbl_name' => 'Name']);
		$html = $this->captureCreateFormGroup($i18n, 'name', ['type' => 'text', 'required' => 'true'], '', 'lbl_');
		$this->assertStringContainsString('<strong>', $html);
	}

	public function testCreateFormGroupDisplaysHelpText(): void {
		$i18n = $this->mockI18n([
			'lbl_name' => 'Name',
			'lbl_name_help' => 'Enter your full name',
		]);
		$html = $this->captureCreateFormGroup($i18n, 'name', ['type' => 'text'], '', 'lbl_');
		$this->assertStringContainsString('Enter your full name', $html);
		$this->assertStringContainsString('form-text', $html);
	}

	public function testCreateFormGroupTextarea(): void {
		$i18n = $this->mockI18n(['lbl_desc' => 'Description']);
		$html = $this->captureCreateFormGroup($i18n, 'desc', ['type' => 'textarea'], 'some text', 'lbl_');
		$this->assertStringContainsString('<textarea', $html);
		$this->assertStringContainsString('some text', $html);
	}

	public function testCreateFormGroupNumberInputHasNumberType(): void {
		$i18n = $this->mockI18n(['lbl_age' => 'Age']);
		$html = $this->captureCreateFormGroup($i18n, 'age', ['type' => 'number'], '5', 'lbl_');
		$this->assertStringContainsString('type=\'number\'', $html);
		$this->assertStringContainsString('value=\'5\'', $html);
	}
}
