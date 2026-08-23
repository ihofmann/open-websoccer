import { expect, test } from '@playwright/test';
import { loginAsUser1, menuItem } from './_helpers';

/**
 * E2E: the "My Office" page – the landing page after login.
 *
 * Seed data: user1 / user1 manages "Team 1"; there is exactly one upcoming
 * match (Team 1 - Team 3).
 */

test.describe('Office page (logged in as user1)', () => {
  test('shows next and last match', async ({ page }) => {
    await loginAsUser1(page);

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Next Match');
    await expect(content).toContainText('Team 1');
    await expect(content).toContainText('Team 3');
    await expect(content).toContainText('My Last Match');
    await expect(content.getByRole('link', { name: 'Formation' })).toBeVisible();
  });

  test('navigation menu links to the team management pages', async ({ page }) => {
    await loginAsUser1(page);

    const menu = page.locator('ul[aria-labelledby="labeloffice"]');
    await page.locator('#labeloffice').click();
    await expect(menu).toBeVisible();
    for (const label of ['My Team', 'Finances', 'Tickets', 'Sponsor']) {
      await expect(menuItem(menu, label)).toBeVisible();
    }

    await menuItem(menu, 'My Team').click();
    await expect(page.locator('h1')).toHaveText('My Players');
  });
});
