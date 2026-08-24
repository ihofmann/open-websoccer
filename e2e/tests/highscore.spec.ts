import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: Frontend "Users High Score Ranking" page.
 *
 * URL:
 *   /?page=highscore
 *
 * The page lists active users (status = 1) with a highscore greater than 0,
 * ranked by highscore DESC, registration date ASC. There is no league
 * selector - the ranking spans the whole game.
 *
 * Table columns: Rank | User | Club | Registration Date | Score
 *
 * Seed data (set in seed/seed_data.sql):
 *   user1: 1500 pts, registered 2024-01-15  -> rank 1  (Team 1)
 *   user2: 1200 pts, registered 2024-02-20  -> rank 2  (Team 2)
 *   user3: 1200 pts, registered 2024-03-10  -> rank 3  (Team 3)
 *   user4:  900 pts, registered 2024-04-05  -> rank 4  (Team 4)
 *   user5:    0 pts                         -> excluded (no highscore)
 *
 * The score column is rendered with Twig's number_format(0, ',', ' '), i.e.
 * a space as the thousands separator: 1500 -> "1 500", 900 -> "900".
 *
 * Guests can browse the page, so no login is required.
 */

/** Return the highscore ranking <table> inside #pagecontent. */
function rankingTable(page: Page): Locator {
  return page.locator('#pagecontent table.table-striped');
}

test.describe('High Score Ranking page', () => {

  test('shows the page title', async ({ page }) => {
    await page.goto('/?page=highscore');

    await expect(page.locator('h1')).toHaveText('Users High Score Ranking');
  });

  test('renders the ranking table with the expected column headers', async ({ page }) => {
    await page.goto('/?page=highscore');

    const table = rankingTable(page);
    await expect(table).toBeVisible();

    const headers = table.locator('thead th');
    await expect(headers).toHaveText([
      'Rank',
      'User',
      'Club',
      'Registration Date',
      'Score',
    ]);
  });

  test('lists 4 users (excludes users with a highscore of 0)', async ({ page }) => {
    await page.goto('/?page=highscore');

    const rows = rankingTable(page).locator('tbody tr');
    await expect(rows).toHaveCount(4);

    // user5 (highscore = 0) must not appear.
    for (let i = 0; i < 4; i++) {
      await expect(rows.nth(i).locator('td').nth(1)).not.toContainText('user5');
    }
  });

  test('ranks user1 first with 1500 points and Team 1', async ({ page }) => {
    await page.goto('/?page=highscore');

    const firstRow = rankingTable(page).locator('tbody tr').nth(0);

    await expect(firstRow.locator('td').nth(0)).toHaveText('1');
    await expect(firstRow.locator('td').nth(1)).toContainText('user1');
    await expect(firstRow.locator('td').nth(2)).toContainText('Team 1');
    await expect(firstRow.locator('td').nth(4)).toHaveText('1 500'); // score
  });

  test('orders all rows by highscore descending', async ({ page }) => {
    await page.goto('/?page=highscore');

    const rows = rankingTable(page).locator('tbody tr');

    // Ranks 1-4, users user1..user4 in score order.
    const expected = [
      { rank: '1', nick: 'user1', score: '1 500' },
      { rank: '2', nick: 'user2', score: '1 200' },
      { rank: '3', nick: 'user3', score: '1 200' },
      { rank: '4', nick: 'user4', score: '900' },
    ];

    for (let i = 0; i < expected.length; i++) {
      const row = rows.nth(i);
      await expect(row.locator('td').nth(0)).toHaveText(expected[i].rank);
      await expect(row.locator('td').nth(1)).toContainText(expected[i].nick);
      await expect(row.locator('td').nth(4)).toHaveText(expected[i].score);
    }
  });

  test('breaks score ties by registration date ascending (user2 before user3)', async ({ page }) => {
    await page.goto('/?page=highscore');

    const rows = rankingTable(page).locator('tbody tr');

    // user2 and user3 both have 1200 pts; user2 registered earlier -> rank 2.
    const rank2 = rows.nth(1);
    await expect(rank2.locator('td').nth(0)).toHaveText('2');
    await expect(rank2.locator('td').nth(1)).toContainText('user2');
    await expect(rank2.locator('td').nth(3)).toHaveText('2024-02-20');

    const rank3 = rows.nth(2);
    await expect(rank3.locator('td').nth(0)).toHaveText('3');
    await expect(rank3.locator('td').nth(1)).toContainText('user3');
    await expect(rank3.locator('td').nth(3)).toHaveText('2024-03-10');
  });

  test('formats the registration date as YYYY-MM-DD', async ({ page }) => {
    await page.goto('/?page=highscore');

    const rows = rankingTable(page).locator('tbody tr');

    await expect(rows.nth(0).locator('td').nth(3)).toHaveText('2024-01-15');
    await expect(rows.nth(3).locator('td').nth(3)).toHaveText('2024-04-05');
  });

  test('formats the score with a space as thousands separator', async ({ page }) => {
    await page.goto('/?page=highscore');

    const rows = rankingTable(page).locator('tbody tr');

    await expect(rows.nth(0).locator('td').nth(4)).toHaveText('1 500');
    await expect(rows.nth(3).locator('td').nth(4)).toHaveText('900');
  });

  test('user name links to the user profile page', async ({ page }) => {
    await page.goto('/?page=highscore');

    const firstRow = rankingTable(page).locator('tbody tr').nth(0);
    // The user cell may contain a Gravatar picture link (alt="user1") in
    // addition to the name link, so target the link by text content rather
    // than accessible name to avoid an ambiguous match.
    const userLink = firstRow.locator('td').nth(1).locator('a').filter({ hasText: 'user1' });
    await expect(userLink).toBeVisible();

    await userLink.click();
    await expect(page.locator('h1')).toHaveText('user1');
  });

  test('club name links to the team details page', async ({ page }) => {
    await page.goto('/?page=highscore');

    const firstRow = rankingTable(page).locator('tbody tr').nth(0);
    const teamLink = firstRow.locator('td').nth(2).locator('a').filter({ hasText: 'Team 1' });
    await expect(teamLink).toBeVisible();

    await teamLink.click();
    await expect(page.locator('h1')).toHaveText('Team 1');
  });

  test('is accessible to guests without logging in', async ({ page }) => {
    // Fresh context - no login performed. The page must still render.
    await page.goto('/?page=highscore');

    await expect(page.locator('h1')).toHaveText('Users High Score Ranking');
    await expect(rankingTable(page).locator('tbody tr')).toHaveCount(4);
  });
});
