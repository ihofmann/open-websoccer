import { test, expect } from '@playwright/test';

/**
 * E2E: user1 logs in to the frontend, navigates to "My Team" and verifies
 * that his players are listed.
 *
 * Seed data:
 *   * user1 / user1  (frontend user, id = 1)
 *   * Team 1 is managed by user1
 *   * Team 1 has 24 players (2 per position_main, 12 position_main values).
 */
test('user1 sees his players on the My Team page', async ({ page }) => {
  // --- Log in to the frontend ------------------------------------------------
  await page.goto('/?page=login');
  await page.fill('#loginstr', 'user1');
  await page.fill('#loginpassword', 'user1');
  await page.click('button[type=submit]');

  // A successful login renders the "My Office" page (the URL keeps the form
  // target, so assert content rather than the URL).
  await expect(page.locator('h1')).toHaveText('My Office');

  // --- Navigate to "My Team" -------------------------------------------------
  await page.goto('/?page=myteam');
  await expect(page.locator('h1')).toHaveText('My Players');

  // Scope to the page content so sidebar blocks cannot interfere.
  const squadTable = page.locator('#pagecontent table');

  // The team has 24 players -> 24 rows in the squad table.
  await expect(squadTable.locator('tbody tr')).toHaveCount(24);

  // Spot-check a few seeded players of Team 1.
  // Naming scheme: Player<teamIdx>_<positionMain><plyrIdx>
  await expect(squadTable).toContainText('Player1_T1');
  await expect(squadTable).toContainText('Player1_LS1');
  await expect(squadTable).toContainText('Player1_RS2');
});
