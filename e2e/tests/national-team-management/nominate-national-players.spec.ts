import { expect, test } from '@playwright/test';
import { loginAs, loginAsUser1, PLAYER_IDS, searchResultRow } from './_helpers';

/**
 * E2E: the "Nominate Players" page – searching for eligible players by name
 * and position, and the nominate (add) action.
 *
 * Seed data (before this spec runs):
 *   Team 41 "England" (user1) has 4 nominated players (IDs 1, 3, 5, 9).
 *   Player 19 was removed by national-team.spec.ts.
 *
 *   All 960 seeded players have nation = "England".  The search excludes
 *   already-nominated and injured players, ordered by strength descending.
 *
 * This spec nominates player 2 (Player1_T2, Torwart).  After this spec,
 * 5 players are in the national team again.
 */

test.describe.serial('Nominate Players page (logged in as user1)', () => {
  test('renders the page title and team name', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    await expect(page.locator('h1')).toHaveText('Nominate Players');
    await expect(page.getByTestId('nt-search-team-name')).toHaveText('England');
  });

  test('shows the search form with all fields', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    const form = page.getByTestId('nt-search-form');
    await expect(form).toBeVisible();

    await expect(page.getByTestId('nt-search-fname')).toBeVisible();
    await expect(page.getByTestId('nt-search-lname')).toBeVisible();
    await expect(page.getByTestId('nt-search-position')).toBeVisible();
    await expect(page.getByTestId('nt-search-position-main')).toBeVisible();
    await expect(page.getByTestId('nt-search-submit')).toBeVisible();
  });

  test('position dropdown has the 4 position options', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    const select = page.getByTestId('nt-search-position');
    const options = select.locator('option');
    // Blank + Torwart + Abwehr + Mittelfeld + Sturm = 5 options.
    await expect(options).toHaveCount(5);
  });

  test('position_main dropdown has all main position options', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    const select = page.getByTestId('nt-search-position-main');
    const options = select.locator('option');
    // Blank + 12 main positions = 13 options.
    await expect(options).toHaveCount(13);
  });

  test('searching by first name returns matching players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    // "Player1_T" matches only Team 1 goalkeepers (Player1_T1, Player1_T2).
    // Player 1 is already nominated → only player 2 appears.
    await page.getByTestId('nt-search-fname').fill('Player1_T');
    await page.getByTestId('nt-search-submit').click();

    // Results table is visible and shows the hit count.
    const table = page.getByTestId('nt-search-results-table');
    await expect(table).toBeVisible();

    const countHeader = page.getByTestId('nt-search-results-count');
    await expect(countHeader).toContainText('1 players found');

    // Each result row has a nominate button.
    await expect(page.getByTestId('nt-search-nominate')).toHaveCount(1);
  });

  test('searching by first name and position narrows results', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    // "Player1" matches teams 1, 10-19 (11 teams × 24 players = 264,
    // minus 5 nominated = 259).  Adding position "Torwart" narrows to
    // only goalkeepers from those teams (11 × 2 = 22, minus 1 nominated = 21).
    await page.getByTestId('nt-search-fname').fill('Player1');
    await page.getByTestId('nt-search-position').selectOption('Torwart');
    await page.getByTestId('nt-search-submit').click();

    const table = page.getByTestId('nt-search-results-table');
    await expect(table).toBeVisible();

    const countHeader = page.getByTestId('nt-search-results-count');
    await expect(countHeader).toContainText('21 players found');

    // entries_per_page = 20, so only 20 rows are shown on the first page.
    const rows = page.getByTestId('nt-search-result-row');
    await expect(rows).toHaveCount(20);
  });

  test('searching by position only returns all uninjured players of that position', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    // Sturm players across all 40 teams: 3 position_main × 2 = 6 per team,
    // 40 × 6 = 240.  Player 19 was removed from the national team by
    // national-team.spec.ts, so all 240 are eligible.
    await page.getByTestId('nt-search-position').selectOption('Sturm');
    await page.getByTestId('nt-search-submit').click();

    const table = page.getByTestId('nt-search-results-table');
    await expect(table).toBeVisible();

    // entries_per_page = 20, so only 20 are shown on the first page.
    const rows = page.getByTestId('nt-search-result-row');
    await expect(rows).toHaveCount(20);

    const countHeader = page.getByTestId('nt-search-results-count');
    await expect(countHeader).toContainText('240 players found');
  });

  test('searching by position_main filters by main or secondary position', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    // "Player1_T" + position_main "T" → only Team 1 goalkeepers with
    // position_main = "T" (IDs 1, 2).  Player 1 is nominated → 1 result.
    await page.getByTestId('nt-search-fname').fill('Player1_T');
    await page.getByTestId('nt-search-position-main').selectOption('T');
    await page.getByTestId('nt-search-submit').click();

    const table = page.getByTestId('nt-search-results-table');
    await expect(table).toBeVisible();

    const rows = page.getByTestId('nt-search-result-row');
    await expect(rows).toHaveCount(1);
  });

  test('search with no matching players shows no-results message', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    await page.getByTestId('nt-search-fname').fill('NonexistentName');
    await page.getByTestId('nt-search-submit').click();

    await expect(page.getByTestId('nt-search-results-table')).toHaveCount(0);
    await expect(page.getByTestId('nt-search-no-results')).toBeVisible();
    await expect(page.getByTestId('nt-search-no-results')).toContainText(
      'No players found.',
    );
  });

  test('search result row shows player name and club', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    await page.getByTestId('nt-search-fname').fill('Player1_T');
    await page.getByTestId('nt-search-submit').click();

    const row = searchResultRow(page, PLAYER_IDS.nominateGoalkeeper);
    await expect(row).toBeVisible();
    await expect(row).toContainText('Player1_T2');
    await expect(row).toContainText('Team 1');
  });

  test('nominating a player shows success and adds them to the team', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    // Search for the eligible goalkeeper.
    await page.getByTestId('nt-search-fname').fill('Player1_T');
    await page.getByTestId('nt-search-submit').click();

    // Nominate player 2 (Player1_T2).
    const row = searchResultRow(page, PLAYER_IDS.nominateGoalkeeper);
    await row.getByTestId('nt-search-nominate').click();

    // The action redirects to the nationalteam page with a success message.
    await expect(page.locator('h1')).toHaveText('National Team');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player has been successfully added to your team.',
    );

    // The player now appears on the national team page.
    await expect(
      page.getByTestId('nt-player-row').filter({ has: page.locator(`[data-player-id="${PLAYER_IDS.nominateGoalkeeper}"]`) }),
    ).toBeVisible();
  });

  test('nominated player no longer appears in search results', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nominate-national-players');

    await page.getByTestId('nt-search-fname').fill('Player1_T');
    await page.getByTestId('nt-search-submit').click();

    // Player 2 was just nominated → no results for "Player1_T".
    await expect(page.getByTestId('nt-search-results-table')).toHaveCount(0);
    await expect(page.getByTestId('nt-search-no-results')).toBeVisible();
  });

  test('logged-in user without national team sees error', async ({ page }) => {
    await loginAs(page, 'user4', 'user4');
    await page.goto('/?page=nominate-national-players');

    await expect(page.locator('.alert-danger')).toContainText(
      'You need to be the manager of a national team in order to use this feature.',
    );
  });
});
