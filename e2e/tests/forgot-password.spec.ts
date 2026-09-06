import { expect, test } from '@playwright/test';

/**
 * E2E: "Forgot password" workflow (frontend + AdminCenter).
 *
 * Security improvement: the application must NOT reveal whether an email
 * address is registered. The "user does not exist" case renders the same
 * success message as a successful reset.
 *
 * There is no mail server in the E2E stack, so mail() fails for existing
 * users. This is expected: the test verifies that the "e-mail not sent"
 * error appears, proving the workflow reached the mail-sending step
 * (i.e. the user was found). Non-existent emails still get the success
 * message with no error.
 */

// ---------------------------------------------------------------------------
// Frontend
// ---------------------------------------------------------------------------

const FRONTEND_SUCCESS_TITLE = 'A new password has been sent';

test.describe('Frontend forgot-password', () => {
  test('existing user: reaches mail-sending step (expected mail error)', async ({ page }) => {
    await page.goto('/?page=forgot-password');
    await expect(page.locator('h1')).toHaveText('Request New Password');

    await page.fill('#useremail', 'user1@example.com');
    await page.click('button[type=submit]');

    // No mail server → mail() fails. The "e-mail not sent" error proves the
    // user was found and the workflow progressed past lookup.
    await expect(page.locator('.alert-danger')).toContainText(/e-mail not sent/i);
  });

  test('non-existent email: shows success message (no error)', async ({ page }) => {
    await page.goto('/?page=forgot-password');
    await expect(page.locator('h1')).toHaveText('Request New Password');

    await page.fill('#useremail', 'does-not-exist@e2e.test');
    await page.click('button[type=submit]');

    // Same success message – no error revealing the email is unknown.
    // The controller returns "login" so the login page is rendered, but the
    // framework does an internal forward (not an HTTP redirect), so the URL
    // stays on the forgot-password page.
    await expect(page.getByTestId('page-title')).toHaveText('Log In');
    await expect(page.locator('.alert-success')).toContainText(FRONTEND_SUCCESS_TITLE);
    await expect(page.locator('.alert-danger')).toHaveCount(0);
  });
});

// ---------------------------------------------------------------------------
// AdminCenter
// ---------------------------------------------------------------------------

const ADMIN_SUCCESS_TITLE = 'Successfully sent password';

test.describe('AdminCenter forgot-password', () => {
  test('existing admin: reaches mail-sending step (expected mail error)', async ({ page }) => {
    await page.goto('/admin/forgot-password.php');
    await expect(page.locator('h1')).toHaveText('Request Password');

    await page.fill('#inputEmail', 'admin@example.com');
    await page.click('button[type=submit]');

    // No mail server → mail() fails. Stays on the forgot-password page with
    // an error, proving the admin was found.
    await expect(page).toHaveURL(/forgot-password\.php$/);
    await expect(page.locator('.alert-danger')).toContainText(/e-mail not sent/i);
  });

  test('non-existent email: shows success page (no error)', async ({ page }) => {
    await page.goto('/admin/forgot-password.php');
    await expect(page.locator('h1')).toHaveText('Request Password');

    await page.fill('#inputEmail', 'does-not-exist@e2e.test');
    await page.click('button[type=submit]');

    // Same redirect and success message – no error revealing the email is unknown.
    await expect(page).toHaveURL(/login\.php\?newpwd=1/);
    await expect(page.locator('.alert-success')).toContainText(ADMIN_SUCCESS_TITLE);
    await expect(page.locator('.alert-danger')).toHaveCount(0);
  });
});
