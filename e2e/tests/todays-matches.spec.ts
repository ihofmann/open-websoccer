import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: Frontend "Today's Matches" page.
 *
 * URL: /?page=todaysmatches
 *
 * The page itself has an empty content block; the matches are rendered by
 * the "todays-matches" block (TodaysMatchesModel → results-list template)
 * in the content_top area. The model queries ws3_spiel for matches with
 * datum within the current calendar day (server time).
 *
 * Seed data:
 *   Match 23 – Friendly, Team 5 2-1 Team 6 (completed today).
 *   Match 24 – Friendly, Team 7 vs Team 8 (scheduled today, not simulated).
 *
 * Guests can browse the page, so no login is required.
 */

function matchesTable(page: Page): Locator {
  return page.locator('#todays-matches_block table');
}

test.describe("Today's Matches page", () => {

  test('shows the page title', async ({ page }) => {
    await page.goto('/?page=todaysmatches');

    await expect(page.locator('h1')).toHaveText("Today's Matches");
  });

  test('lists matches scheduled for today', async ({ page }) => {
    await page.goto('/?page=todaysmatches');

    const table = matchesTable(page);
    await expect(table).toBeVisible({ timeout: 10_000 });

    // 2 seeded friendly matches for today.
    await expect(table.locator('tbody tr')).toHaveCount(2);

    // Both Team 5/Team 6 and Team 7/Team 8 should be listed.
    await expect(table).toContainText('Team 5');
    await expect(table).toContainText('Team 6');
    await expect(table).toContainText('Team 7');
    await expect(table).toContainText('Team 8');
  });

  test('completed match shows score', async ({ page }) => {
    await page.goto('/?page=todaysmatches');

    const table = matchesTable(page);
    await expect(table).toBeVisible({ timeout: 10_000 });

    // Match 23 (completed): Team 5 2-1 Team 6.
    await expect(table).toContainText('2 - 1');

    // Match 24 may or may not have been simulated by the AdminCenter jobs
    // test (which runs earlier in the suite). If it was simulated, a score
    // is shown; if not, a "Match Details" link is shown. Either way, both
    // matches must be listed.
    await expect(table.locator('tbody tr')).toHaveCount(2);
  });

  test('match result links to match details page', async ({ page }) => {
    await page.goto('/?page=todaysmatches');

    const table = matchesTable(page);
    await expect(table).toBeVisible({ timeout: 10_000 });

    // Click the completed match result link (2 - 1).
    await table.getByRole('link', { name: '2 - 1' }).click();

    await expect(page.locator('h1')).toHaveText('Team 5 - Team 6');
  });
});
