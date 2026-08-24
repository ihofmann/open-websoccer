import { expect, test } from '@playwright/test';

/**
 * E2E: AdminCenter e-mail second factor verification.
 *
 * After correct credentials the admin is asked for a verification code.
 * There is no mail server in the E2E stack, so the code is rendered on the
 * page with the "E-Mail could not be sent" warning.  Entering the displayed
 * code completes the login.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */
test('admin sees verification code page and can log in with displayed code', async ({ page }) => {
  // Step 1: credentials
  await page.goto('/admin/');
  await expect(page).toHaveURL(/login\.php/);
  await page.fill('#inputUser', 'admin');
  await page.fill('#inputPassword', 'admin');
  await page.click('button[type=submit]');

  // Step 2: verification code page
  await expect(page.locator('h2')).toHaveText('Verification Code');
  await expect(page.locator('.alert-warning')).toContainText(/E-Mail could not be sent/i);
  await expect(page.locator('.alert-warning')).toContainText(/Here is the verification code:/i);

  // Extract the 6-digit code from the warning message
  const text = await page.locator('.alert-warning').textContent();
  const match = text!.match(/(\d{6})/);
  expect(match).not.toBeNull();
  const code = match![1];

  // Enter and submit the code
  await page.fill('#inputVerificationCode', code);
  await page.click('button[type=submit]');

  // Login completed
  await expect(page).toHaveURL(/\/admin\/index\.php$/);
  await expect(page.locator('.navbar-text')).toContainText('admin');
});

test('wrong verification code shows error and stays on verification page', async ({ page }) => {
  // Step 1: credentials
  await page.goto('/admin/');
  await page.fill('#inputUser', 'admin');
  await page.fill('#inputPassword', 'admin');
  await page.click('button[type=submit]');

  await expect(page.locator('h2')).toHaveText('Verification Code');

  // Submit a wrong code
  await page.fill('#inputVerificationCode', '999999');
  await page.click('button[type=submit]');

  // Error message shown, still on login page with verification form
  await expect(page.locator('.alert-danger')).toContainText(/Wrong verification code/i);
  await expect(page.locator('h2')).toHaveText('Verification Code');
});
