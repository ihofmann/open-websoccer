import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './entities/helpers';

/**
 * E2E: admin signs out of AdminCenter and cannot use it until they log in again.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */
test('admin can log out of AdminCenter', async ({ page }) => {
  await loginAsAdmin(page);

  await page.getByRole('navigation').getByRole('link', { name: 'Log Out' }).first().click();
  await expect(page).toHaveURL(/login\.php\?loggedout=1/);
  await expect(page.locator('.alert-success')).toContainText('Successfully logged out.');
  await expect(page.locator('.alert-success')).toContainText('See you soon!');

  await page.goto('/admin/');
  await expect(page).toHaveURL(/login\.php/);
  await expect(page.locator('h1')).toHaveText('AdminCenter Log In');
});
