import { expect, test } from '@playwright/test';

/**
 * E2E: Frontend "Match Details" page.
 *
 * URL: /?page=match&id=<matchId>
 *
 * Seed data:
 *   Match 1  – League, matchday 1: Team 1 3-0 Team 2 (completed).
 *   Match 11 – League, matchday 2: Team 1 vs Team 3 (future, not simulated).
 *   Match 21 – Cup: Team 1 2-1 Team 2, "Demo Cup" / "First Round" (completed).
 *
 * Guests can browse the page, so no login is required.
 */

test.describe('Match Details page', () => {

  test('completed league match shows score and match type', async ({ page }) => {
    // Match 1: Team 1 3-0 Team 2, League, matchday 1.
    await page.goto('/?page=match&id=1');

    await expect(page.locator('h1')).toHaveText('Team 1 - Team 2');

    // Match type: "League, matchday 1".
    const content = page.locator('#pagecontent');
    await expect(content).toContainText('League');
    await expect(content).toContainText('matchday 1');

    // Score: 3 - 0.
    await expect(page.locator('#report_goals_home')).toHaveText('3');
    await expect(page.locator('#report_goals_separator')).toHaveText('-');
    await expect(page.locator('#report_goals_guest')).toHaveText('0');

    // The "match completed" notice should be present.
    await expect(content).toContainText('The match is completed.');
  });

  test('completed league match shows team names with info links', async ({ page }) => {
    await page.goto('/?page=match&id=1');

    // The report area shows team names as text with a small info-icon link.
    const homeSpan = page.locator('.report_team_home');
    const guestSpan = page.locator('.report_team_guest');
    await expect(homeSpan).toContainText('Team 1');
    await expect(guestSpan).toContainText('Team 2');

    // Each team name has an info link to the team page.
    await expect(homeSpan.locator('a')).toHaveAttribute('href', /page=team/);
    await expect(guestSpan.locator('a')).toHaveAttribute('href', /page=team/);
  });

  test('future match shows "not yet simulated" notice', async ({ page }) => {
    // Match 11: Team 1 vs Team 3, matchday 2, ~10 years in the future.
    await page.goto('/?page=match&id=11');

    await expect(page.locator('h1')).toHaveText('Team 1 - Team 3');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('This match has not been played yet.');
  });

  test('completed cup match shows cup name and round', async ({ page }) => {
    // Match 21: Team 1 2-1 Team 2, Demo Cup, First Round.
    await page.goto('/?page=match&id=21');

    await expect(page.locator('h1')).toHaveText('Team 1 - Team 2');

    const content = page.locator('#pagecontent');
    // Match type for cup matches: "Cup, <cupName> (<round>)".
    await expect(content).toContainText('Cup');
    await expect(content).toContainText('Demo Cup');
    await expect(content).toContainText('First Round');

    // Score: 2 - 1.
    await expect(page.locator('#report_goals_home')).toHaveText('2');
    await expect(page.locator('#report_goals_separator')).toHaveText('-');
    await expect(page.locator('#report_goals_guest')).toHaveText('1');

    await expect(content).toContainText('The match is completed.');
  });

  test('match with stadium shows stadium name', async ({ page }) => {
    // Match 1 is linked to stadion_id=1 ("Sample Arena").
    await page.goto('/?page=match&id=1');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Sample Arena');
  });

  test('match report tab is active and players tab exists', async ({ page }) => {
    await page.goto('/?page=match&id=1');

    const reportTab = page.locator('#reportTab .nav-link', { hasText: 'Live Comments' });
    await expect(reportTab).toHaveClass(/active/);

    const playersTab = page.locator('#reportTab .nav-link', { hasText: 'Players' });
    await expect(playersTab).toBeVisible();

    const statsTab = page.locator('#reportTab .nav-link', { hasText: 'Statistics' });
    await expect(statsTab).toBeVisible();
  });

  test('invalid match id shows error page', async ({ page }) => {
    await page.goto('/?page=match&id=99999');

    // The application throws an Exception caught by index.php, rendering the
    // error template with h1 = "Error" and the exception message in an alert.
    await expect(page.locator('h1')).toHaveText('Error');
    await expect(page.locator('#pagecontent .alert')).toContainText(
      'The requested page does not exist'
    );
  });
});
