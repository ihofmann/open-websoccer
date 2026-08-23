import { expect, test } from '@playwright/test';
import { loginAsUser1 } from './_helpers';

/**
 * E2E: the "Sponsor" page.
 *
 * Seed data: Team 1 already has a sponsor ("Sponsor Alpha"), and the season
 * has just started, so a change is blocked.
 */

test('sponsor page blocks a sponsor change early in the season', async ({ page }) => {
  await loginAsUser1(page);
  await page.goto('/?page=sponsor');
  await expect(page.locator('h1')).toHaveText('Sponsor');
  await expect(page.locator('#pagecontent')).toContainText(
    'You may choose a new sponsor only after the 4th match day.',
  );
});
