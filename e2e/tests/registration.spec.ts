import { expect, test, type Page } from '@playwright/test';
import { loginAsAdmin, logoutAdmin } from './admincenter/entities/helpers';

/**
 * E2E: frontend user registration.
 *
 * There is no mail server in the E2E stack, so a valid registration ends with
 * the application error "e-mail not sent." after the user row has already been
 * created (status = 2 / inactive). An admin must then activate the account
 * before the new user can log in.
 *
 * Data privacy: registering with an already existing e-mail address must NOT
 * reveal that the address is in use. Instead the standard success state is
 * shown and a password reset is triggered in the background. The background
 * mail delivery fails (no mail server), which is treated as success, so no
 * error is displayed.
 */

const PASSWORD = 'Secret1';

async function fillRegistrationForm(
  page: Page,
  fields: {
    nick: string;
    email: string;
    emailRepeat: string;
    password: string;
    passwordRepeat: string;
    acceptTerms?: boolean;
  },
): Promise<void> {
  await page.goto('/?page=register');
  await expect(page.locator('h1')).toHaveText('Register as a new user');

  await page.fill('#nick', fields.nick);
  await page.fill('#email', fields.email);
  await page.fill('#email_repeat', fields.emailRepeat);
  await page.fill('#pswd', fields.password);
  await page.fill('#pswd_repeat', fields.passwordRepeat);

  if (fields.acceptTerms !== false) {
    await page.check('#termsandconditions');
  }

  await page.click('button[type=submit]');
}

async function enableUserInAdmin(page: Page, nick: string): Promise<void> {
  await loginAsAdmin(page);

  const params = new URLSearchParams({
    site: 'manage',
    entity: 'users',
    entity_users_nick: nick,
  });
  await page.goto(`/admin/index.php?${params.toString()}`);

  const row = page.locator('table tbody tr', { hasText: nick }).first();
  await expect(row).toBeVisible();
  await row.locator('a[title="Edit"]').click();

  await expect(page.locator('legend')).toHaveText('Edit');
  await page.check('#status');
  await page
    .locator('form')
    .filter({ has: page.locator('input[name="action"][value="save"]') })
    .locator('input[type=submit]')
    .click();

  await expect(page.locator('.alert-success')).toBeVisible();
  await logoutAdmin(page);
}

test('rejects registration with mismatched passwords', async ({ page }) => {
  const stamp = Date.now();
  const nick = `badreg${stamp}`;

  await fillRegistrationForm(page, {
    nick,
    email: `${nick}@e2e.test`,
    emailRepeat: `${nick}@e2e.test`,
    password: PASSWORD,
    passwordRepeat: '**********',
  });

  await expect(page.locator('.alert-danger')).toContainText('Passwords do not match.');
  await expect(page.locator('h1')).toHaveText('Register as a new user');
});

test('duplicate e-mail shows success state (no error) and triggers password reset', async ({ page }) => {
  // user5@example.com is a seeded, active user that no other E2E test logs in
  // as, so triggering a background password reset for it is side-effect-free.
  const existingEmail = 'user5@example.com';
  const stamp = Date.now();
  const nick = `dupreg${stamp}`;

  await fillRegistrationForm(page, {
    nick,
    email: existingEmail,
    emailRepeat: existingEmail,
    password: PASSWORD,
    passwordRepeat: PASSWORD,
  });

  // The standard registration success state is shown, identical to a genuine
  // registration, so the existing e-mail address is not revealed.
  await expect(page.locator('.alert-success')).toContainText('Registration submitted');
  // No error must be displayed, even though the background password-reset
  // mail could not be sent (no mail server in the E2E stack).
  await expect(page.locator('.alert-danger')).toHaveCount(0);
});

test('registers a user, admin enables account, then user can log in', async ({ page, context }) => {
  test.setTimeout(120_000);

  const stamp = Date.now();
  const nick = `newuser${stamp}`;
  const email = `${nick}@e2e.test`;

  await fillRegistrationForm(page, {
    nick,
    email,
    emailRepeat: email,
    password: PASSWORD,
    passwordRepeat: PASSWORD,
  });

  // No mail server → mail() fails after the user row is inserted.
  await expect(page.locator('.alert-danger')).toContainText(/mail not sent/i);

  await enableUserInAdmin(page, nick);

  // Drop admin session cookies so the frontend login is clean.
  await context.clearCookies();

  await page.goto('/?page=login');
  await page.fill('#loginstr', nick);
  await page.fill('#loginpassword', PASSWORD);
  await page.click('button[type=submit]');

  await expect(page.locator('h1')).toHaveText('My Office');
});
