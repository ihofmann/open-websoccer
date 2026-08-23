import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Stadium Environment" page.
 *
 * Seed data: Team 1 has the "Youth Center" building; other buildings such as
 * the "Medical Center" are available for construction.
 */

test('stadium environment page shows existing and available buildings', async ({ page }) => {
  await loginAsUser1(page);
  await page.goto('/?page=stadiumenvironment');
  await expect(page.locator('h1')).toHaveText('Stadium Environment');

  const content = page.locator('#pagecontent');
  await expect(content).toContainText('Existing buildings');
  await expect(content).toContainText('Youth Center');
  await expect(content).toContainText('Available buildings');
  await expect(content).toContainText('Medical Center');
  await expect(content).toContainText(`80 000 ${CURRENCY}`);
  await expect(content.getByRole('link', { name: 'Build' }).first()).toBeVisible();
});
