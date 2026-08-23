import { expect, test, type Page } from '@playwright/test';

/**
 * E2E: AdminCenter e-mail second factor lockout.
 *
 * After three wrong verification-code attempts the account is blocked for
 * 5 minutes.  Even with correct credentials the blocked message appears.
 *
 * This test uses the `locktest` admin (seed: locktest / locktest), which is
 * NOT used by any other test, so blocking it cannot interfere with the rest
 * of the suite.
 *
 * There is no mail server in the E2E stack, so the verification code is
 * rendered on the page (expected mail failure).
 */

const BLOCKED_MESSAGE = /You can try again in 5 minutes/i;
const WRONG_CODE_MESSAGE = /Wrong verification code/i;

/** Submits credentials and returns the 6-digit code displayed on the page. */
async function submitCredentialsAndGetCode(page: Page, user: string, password: string): Promise<string> {
  await page.goto('/admin/');
  await page.fill('#inputUser', user);
  await page.fill('#inputPassword', password);
  await page.click('button[type=submit]');

  await expect(page.locator('h2')).toHaveText('Verification Code');
  await expect(page.locator('.alert-warning')).toContainText(/E-Mail could not be sent/i);
  const text = await page.locator('.alert-warning').textContent();
  const match = text!.match(/(\d{6})/);
  if (!match) {
    throw new Error('Could not extract the 6-digit verification code from the page.');
  }
  return match[1];
}

test('admin is blocked after three wrong verification codes', async ({ page }) => {
  // Step 1: correct credentials → verification page with displayed code
  const correctCode = await submitCredentialsAndGetCode(page, 'locktest', 'locktest');

  // Attempt 1: wrong code
  await page.fill('#inputVerificationCode', '000000');
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-danger')).toContainText(WRONG_CODE_MESSAGE);
  await expect(page).toHaveURL(/login\.php$/);

  // Attempt 2: wrong code
  await page.fill('#inputVerificationCode', '111111');
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-danger')).toContainText(WRONG_CODE_MESSAGE);

  // Attempt 3: wrong code → blocked
  await page.fill('#inputVerificationCode', '222222');
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-danger')).toContainText(BLOCKED_MESSAGE);

  // Even with the correct code the account stays blocked
  await page.fill('#inputVerificationCode', correctCode);
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-danger')).toContainText(BLOCKED_MESSAGE);

  // Even re-entering correct credentials shows the blocked message
  await page.goto('/admin/');
  await page.fill('#inputUser', 'locktest');
  await page.fill('#inputPassword', 'locktest');
  await page.click('button[type=submit]');
  await expect(page.locator('.alert-danger')).toContainText(BLOCKED_MESSAGE);
  // Still on the credentials page (not the verification step)
  await expect(page.locator('#inputUser')).toBeVisible();
});
