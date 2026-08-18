import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './entities/helpers';

/**
 * E2E: AdminCenter login is written to the admin log and can be viewed / truncated.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */
test('admin login is listed in the login logs and the log file can be emptied', async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto('/admin/index.php?site=all_logging');
  await expect(page.locator('h1')).toHaveText('Admin Log');
  await expect(page.locator('table')).toContainText('admin');

  await page.getByRole('button', { name: 'Empty File' }).click();
  await expect(page.locator('.alert-success')).toContainText(
    'The log file has been truncated successfully.',
  );
  await expect(page.locator('table')).toContainText('Truncated by admin');
});
