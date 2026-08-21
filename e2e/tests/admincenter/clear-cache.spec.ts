import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './entities/helpers';

/**
 * E2E: admin rebuilds the configuration and template cache from AdminCenter.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */
test('admin can clear the cache', async ({ page }) => {
  await loginAsAdmin(page);

  await page.getByRole('link', { name: 'Clear Cache' }).click();
  await expect(page).toHaveURL(/site=clearcache/);
  await expect(page.locator('h1')).toHaveText('Clear Cache');
  await expect(page.locator('.alert-success')).toContainText('The cache has been rebuilt.');
});
