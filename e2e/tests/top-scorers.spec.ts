import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: Frontend "Top Scorers" and "Top Strikers" pages.
 *
 * URLs:
 *   /?page=topscorers[&leagueid=<id>]
 *   /?page=topstrikers[&leagueid=<id>]
 *
 * Both pages show a league selector dropdown and a table of players ranked
 * by their season statistics. Without a leagueid the ranking spans all
 * leagues; with leagueid=N it is filtered to that league.
 *
 * Seed data (player season statistics, all in League 1 teams):
 *   Player9_LS1  (Team 9):  5 goals, 2 assists, 1 match → score 7  ← #1 scorer
 *   Player15_LS1 (Team 15): 3 goals, 2 assists, 1 match → score 5
 *   Player9_RS1  (Team 9):  3 goals, 1 assist,  1 match → score 4
 *   Player1_LS1  (Team 1):  3 goals, 0 assists, 1 match → score 3  ← #1 striker (no assists)
 *   Player8_LS1  (Team 8):  2 goals, 1 assist,  1 match → score 3
 *   Player20_LS1 (Team 20): 2 goals, 0 assists, 1 match → score 2
 *
 * Top-Scorers ordering: score DESC, assists DESC, goals DESC, matches ASC, id ASC.
 *   1. Player9_LS1  (7)   2. Player15_LS1 (5)   3. Player9_RS1 (4)
 *   4. Player1_LS1  (3)   5. Player8_LS1  (3)   6. Player20_LS1 (2)
 *   (Player1_LS1 and Player8_LS1 both score 3; Player1 has 0 assists < 1, so
 *    Player8_LS1 ranks higher. Wait — assists DESC means more assists first.
 *    Player8_LS1 has 1 assist, Player1_LS1 has 0 → Player8_LS1 is #4,
 *    Player1_LS1 is #5.)
 *
 *   Corrected Top-Scorers order:
 *   1. Player9_LS1  (score 7, 2 assists)
 *   2. Player15_LS1 (score 5, 2 assists)
 *   3. Player9_RS1  (score 4, 1 assist)
 *   4. Player8_LS1  (score 3, 1 assist)
 *   5. Player1_LS1  (score 3, 0 assists)
 *   6. Player20_LS1 (score 2, 0 assists)
 *
 * Top-Strikers ordering: goals DESC, matches ASC.
 *   1. Player9_LS1  (5 goals)   2. Player15_LS1 (3 goals)
 *   3. Player9_RS1  (3 goals)   4. Player1_LS1  (3 goals)
 *   5. Player8_LS1  (2 goals)   6. Player20_LS1 (2 goals)
 *   (Players with 3 goals and 1 match are tied → id ASC decides.)
 *
 * Guests can browse the pages, so no login is required.
 */

const LEAGUE_ID = 1;

/** Return the player ranking <table> inside #pagecontent. */
function rankingTable(page: Page): Locator {
  return page.locator('#pagecontent table.table-striped');
}

// ---------------------------------------------------------------------------
// Top Scorers
// ---------------------------------------------------------------------------

test.describe('Top Scorers page', () => {

  test('shows page title and league selector', async ({ page }) => {
    await page.goto('/?page=topscorers');

    await expect(page.locator('h1')).toHaveText('Top Scorers');

    const select = page.locator('#leagueid');
    await expect(select).toBeVisible();
    const options = select.locator('option');
    // Placeholder + 2 leagues.
    await expect(options).toHaveCount(3);
  });

  test('lists 6 players when filtered to League 1', async ({ page }) => {
    await page.goto(`/?page=topscorers&leagueid=${LEAGUE_ID}`);

    const table = rankingTable(page);
    await expect(table).toBeVisible();
    await expect(table.locator('tbody tr')).toHaveCount(6);
  });

  test('ranks Player9_LS1 first with 5 goals, 2 assists, score 7', async ({ page }) => {
    await page.goto(`/?page=topscorers&leagueid=${LEAGUE_ID}`);

    const rows = rankingTable(page).locator('tbody tr');
    const firstRow = rows.nth(0);

    // Columns: # | Name | Team | Goals | Assists | Score
    await expect(firstRow.locator('td').nth(0)).toHaveText('1');
    await expect(firstRow.locator('td').nth(1)).toContainText('Player9_LS1');
    await expect(firstRow.locator('td').nth(2)).toContainText('Team 9');
    await expect(firstRow.locator('td').nth(3)).toHaveText('5');  // goals
    await expect(firstRow.locator('td').nth(4)).toHaveText('2');  // assists
    await expect(firstRow.locator('td').nth(5)).toHaveText('7');  // score
  });

  test('ranks Player15_LS1 second with score 5', async ({ page }) => {
    await page.goto(`/?page=topscorers&leagueid=${LEAGUE_ID}`);

    const rows = rankingTable(page).locator('tbody tr');
    const secondRow = rows.nth(1);

    await expect(secondRow.locator('td').nth(0)).toHaveText('2');
    await expect(secondRow.locator('td').nth(1)).toContainText('Player15_LS1');
    await expect(secondRow.locator('td').nth(5)).toHaveText('5'); // score
  });

  test('orders by assists as tie-breaker: Player8_LS1 before Player1_LS1', async ({ page }) => {
    await page.goto(`/?page=topscorers&leagueid=${LEAGUE_ID}`);

    const rows = rankingTable(page).locator('tbody tr');

    // Position 4: Player8_LS1 (score 3, 1 assist).
    const pos4 = rows.nth(3);
    await expect(pos4.locator('td').nth(0)).toHaveText('4');
    await expect(pos4.locator('td').nth(1)).toContainText('Player8_LS1');
    await expect(pos4.locator('td').nth(4)).toHaveText('1'); // assists

    // Position 5: Player1_LS1 (score 3, 0 assists).
    const pos5 = rows.nth(4);
    await expect(pos5.locator('td').nth(0)).toHaveText('5');
    await expect(pos5.locator('td').nth(1)).toContainText('Player1_LS1');
    await expect(pos5.locator('td').nth(4)).toHaveText('0'); // assists
  });

  test('player name links to the player details page', async ({ page }) => {
    await page.goto(`/?page=topscorers&leagueid=${LEAGUE_ID}`);

    const rows = rankingTable(page).locator('tbody tr');
    const playerLink = rows.nth(0).locator('td').nth(1).getByRole('link');
    await expect(playerLink).toBeVisible();
    await expect(playerLink).toContainText('Player9_LS1');

    // Click through to the player details page and verify the heading.
    await playerLink.click();
    await expect(page.locator('h1')).toContainText('Player9_LS1');
  });

  test('shows all leagues when no league is selected', async ({ page }) => {
    await page.goto('/?page=topscorers');

    const table = rankingTable(page);
    await expect(table).toBeVisible();
    // All 6 seeded players with stats are in League 1; League 2 has none.
    await expect(table.locator('tbody tr')).toHaveCount(6);
  });
});

// ---------------------------------------------------------------------------
// Top Strikers
// ---------------------------------------------------------------------------

test.describe('Top Strikers page', () => {

  test('shows page title and league selector', async ({ page }) => {
    await page.goto('/?page=topstrikers');

    await expect(page.locator('h1')).toHaveText('Best Strikers in Game');

    const select = page.locator('#leagueid');
    await expect(select).toBeVisible();
  });

  test('lists 6 players when filtered to League 1', async ({ page }) => {
    await page.goto(`/?page=topstrikers&leagueid=${LEAGUE_ID}`);

    const table = rankingTable(page);
    await expect(table).toBeVisible();
    await expect(table.locator('tbody tr')).toHaveCount(6);
  });

  test('ranks Player9_LS1 first with 5 goals', async ({ page }) => {
    await page.goto(`/?page=topstrikers&leagueid=${LEAGUE_ID}`);

    const rows = rankingTable(page).locator('tbody tr');
    const firstRow = rows.nth(0);

    // Columns: # | Name | Team | Matches | Goals
    await expect(firstRow.locator('td').nth(0)).toHaveText('1');
    await expect(firstRow.locator('td').nth(1)).toContainText('Player9_LS1');
    await expect(firstRow.locator('td').nth(2)).toContainText('Team 9');
    await expect(firstRow.locator('td').nth(3)).toHaveText('1');  // matches
    await expect(firstRow.locator('td').nth(4)).toHaveText('5');  // goals
  });

  test('ranks by goals: 3-goal players before 2-goal players', async ({ page }) => {
    await page.goto(`/?page=topstrikers&leagueid=${LEAGUE_ID}`);

    const rows = rankingTable(page).locator('tbody tr');

    // Positions 2-4 should all have 3 goals.
    for (let i = 1; i <= 3; i++) {
      await expect(rows.nth(i).locator('td').nth(4)).toHaveText('3'); // goals
    }

    // Positions 5-6 should have 2 goals.
    for (let i = 4; i <= 5; i++) {
      await expect(rows.nth(i).locator('td').nth(4)).toHaveText('2'); // goals
    }
  });
});
