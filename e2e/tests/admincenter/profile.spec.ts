import { test, expect, type Page } from '@playwright/test';
import { loginAsAdmin, logoutAdmin, submitPageSaveForm } from './entities/helpers';

const ORIGINAL_PASSWORD = 'admin';
const NEW_PASSWORD = 'E2eAdmin9';

async function saveOwnPassword(page: Page, password: string): Promise<void> {
  await page.goto('/admin/index.php?site=profile');
  await expect(page.locator('h1')).toHaveText('Edit Profile');
  await page.fill('#newpassword', password);
  await page.fill('#repeatpassword', password);
  await submitPageSaveForm(page);
  await expect(page.locator('.alert-success')).toContainText('Data successfully saved.');
}

/**
 * E2E: admin changes their own password and can sign in with the new one.
 *
 * The seeded password is restored afterwards so later tests can still use
 * `admin` / `admin`.
 */
test('admin can change own password and log in with it', async ({ page }) => {
  let currentPassword = ORIGINAL_PASSWORD;
  await loginAsAdmin(page);

  try {
    await saveOwnPassword(page, NEW_PASSWORD);
    currentPassword = NEW_PASSWORD;

    await logoutAdmin(page);
    await loginAsAdmin(page, NEW_PASSWORD);
    await expect(page.locator('.navbar-text')).toContainText('admin');
  } finally {
    await page.goto('/admin/');
    if (page.url().includes('login.php')) {
      await loginAsAdmin(page, currentPassword);
    }
    if (currentPassword !== ORIGINAL_PASSWORD) {
      await saveOwnPassword(page, ORIGINAL_PASSWORD);
    }
  }
});
