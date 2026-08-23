import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Stadium" page.
 *
 * Seed data: Team 1 has stadium "Sample Arena" (capacity 18 200), with a
 * construction already in progress by "Builder Inc."
 */

test('stadium page shows the stadium, upgrades and running construction', async ({ page }) => {
  await loginAsUser1(page);
  await page.goto('/?page=stadium');
  await expect(page.locator('h1')).toHaveText('Stadium');

  const content = page.locator('#pagecontent');
  await expect(content).toContainText('Sample Arena');
  await expect(content).toContainText('18 200');
  await expect(content).toContainText('Maintenance and Upgrading');
  await expect(content).toContainText('Grass quality');
  await expect(content).toContainText(`13 000 ${CURRENCY}`);

  // The stadium is drawn on a canvas.
  await expect(page.locator('canvas#stadium')).toBeVisible();

  // An extension is already ordered in the seed data, so no new order form.
  await expect(content).toContainText('Construction is currently in progress');
  await expect(content).toContainText('Builder Inc.');
});
