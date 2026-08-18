import { test, expect } from '@playwright/test';
import { loginAsAdmin, submitPageSaveForm } from './entities/helpers';

/**
 * E2E: admin updates the imprint in AdminCenter and the public website shows it.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */
test('changing the imprint is reflected on the website', async ({ page }) => {
  const marker = `E2E imprint ${Date.now()}`;

  await loginAsAdmin(page);
  await page.goto('/admin/index.php?site=imprint');
  await expect(page.locator('h1')).toHaveText('Imprint');

  await page.fill('#content', marker);
  await submitPageSaveForm(page);
  await expect(page.locator('.alert-success')).toContainText('Data successfully saved.');

  await page.goto('/');
  await page.getByRole('contentinfo').getByRole('link', { name: 'Imprint' }).click();
  await expect(page.locator('h1')).toHaveText('Imprint');
  await expect(page.locator('#pagecontent')).toContainText(marker);
});
