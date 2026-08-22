import { expect, test } from '@playwright/test';

/**
 * E2E: Frontend "Table History" page.
 *
 * URL: /?page=tablehistory&id=<teamId>
 *
 * The page renders a chart (flot.js) showing the team's league rank per
 * matchday. The chart container #leaguehistorychart has data attributes:
 *   data-maxpos  – number of teams in the league
 *   data-series  – JSON array of [matchday, rank] pairs
 *
 * Seed data:
 *   Team 9 is ranked #1 after matchday 1 (League 1, Season 1).
 *   League 1 has 20 teams.
 *
 * Guests can browse the page, so no login is required.
 */

test.describe('Table History page', () => {

  test('shows the team name in the page title', async ({ page }) => {
    await page.goto('/?page=tablehistory&id=9');

    // Title: "Season history of Team 9" (tablehistory_title message).
    await expect(page.locator('h1')).toContainText('Team 9');
  });

  test('renders the chart with correct data attributes', async ({ page }) => {
    await page.goto('/?page=tablehistory&id=9');

    const chart = page.locator('#leaguehistorychart');
    await expect(chart).toBeVisible();

    // 20 teams in League 1.
    await expect(chart).toHaveAttribute('data-maxpos', '20');

    // Team 9 is ranked 1 after matchday 1 → data-series contains [1, 1].
    const series = await chart.getAttribute('data-series');
    expect(series).toContain('[1, 1]');
  });

  test('shows a back-to-league button', async ({ page }) => {
    await page.goto('/?page=tablehistory&id=9');

    const backButton = page.locator('#pagecontent a.btn', { hasText: 'Table' });
    await expect(backButton).toBeVisible();
    await expect(backButton).toHaveAttribute('href', /page=league/);
    await expect(backButton).toHaveAttribute('href', /id=1/);
  });

  test('invalid team id shows error page', async ({ page }) => {
    await page.goto('/?page=tablehistory&id=99999');

    await expect(page.locator('h1')).toHaveText('Error');
    await expect(page.locator('#pagecontent .alert')).toContainText(
      'The requested page does not exist'
    );
  });
});
