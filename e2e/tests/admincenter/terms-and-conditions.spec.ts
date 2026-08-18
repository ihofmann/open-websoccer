import { test, expect } from '@playwright/test';
import { loginAsAdmin, submitPageSaveForm } from './entities/helpers';

/**
 * E2E: admin updates the English terms and conditions; the public website shows them.
 *
 * Seed data: AdminCenter login is `admin` / `admin` (language `en`).
 * The original terms are restored so later runs (e.g. `--keep`) stay stable.
 */
test('changing terms and conditions is reflected on the website', async ({ page }) => {
  const marker = `E2E terms ${Date.now()}`;

  await loginAsAdmin(page);
  await page.goto('/admin/index.php?site=termsandconditions');
  await expect(page.locator('h1')).toHaveText('Terms and conditions');

  await page.selectOption('#lang', 'en');
  await page.locator('form').filter({ has: page.locator('#lang') }).getByRole('button').click();
  await expect(page.locator('#content')).toBeVisible();

  const original = await page.locator('#content').inputValue();

  try {
    await page.fill('#content', marker);
    await submitPageSaveForm(page);
    await expect(page.locator('.alert-success')).toContainText('Data successfully saved.');

    await page.goto('/');
    await page.getByRole('contentinfo').getByRole('link', { name: 'Terms and Conditions' }).click();
    await expect(page.locator('h1')).toHaveText('Terms and Conditions');
    await expect(page.locator('#pagecontent')).toContainText(marker);
  } finally {
    await page.goto('/admin/index.php?site=termsandconditions');
    await page.selectOption('#lang', 'en');
    await page.locator('form').filter({ has: page.locator('#lang') }).getByRole('button').click();
    await page.fill('#content', original);
    await submitPageSaveForm(page);
  }
});
