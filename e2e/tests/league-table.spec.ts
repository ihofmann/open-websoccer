import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: Frontend "Leagues" page (league table / standings).
 *
 * URL: /?page=leagues&id=<leagueId>
 *
 * The page renders a league selector dropdown and the "leaguetable" block
 * (LeagueTableModel) that shows the current standings with table markers.
 *
 * Seed data (League 1 = "Premier Sample League", 20 teams):
 *   Matchday 1 results (all berechnet = 1):
 *     Team 1  3-0 Team 2     Team 9  4-0 Team 10
 *     Team 3  2-1 Team 4     Team 11 1-0 Team 12
 *     Team 5  1-1 Team 6     Team 13 2-2 Team 14
 *     Team 7  0-2 Team 8     Team 15 3-1 Team 16
 *     Team 17 0-0 Team 18    Team 19 1-2 Team 20
 *
 *   Expected standings order (score DESC, GD DESC, W DESC, D DESC, goals DESC,
 *   name ASC):
 *     1. Team 9  (3 pts, GD +4)   11. Team 6  (1 pt)
 *     2. Team 1  (3 pts, GD +3)   12. Team 17 (1 pt)
 *     3. Team 15 (3 pts, GD +2)   13. Team 18 (1 pt)
 *     4. Team 8  (3 pts, GD +2)   14. Team 19 (0 pts, GD -1)
 *     5. Team 20 (3 pts, GD +1)   15. Team 4  (0 pts, GD -1)
 *     6. Team 3  (3 pts, GD +1)   16. Team 12 (0 pts, GD -1)
 *     7. Team 11 (3 pts, GD +1)   17. Team 16 (0 pts, GD -2)
 *     8. Team 13 (1 pt, goals 2)  18. Team 7  (0 pts, GD -2)
 *     9. Team 14 (1 pt, goals 2)  19. Team 2  (0 pts, GD -3)
 *    10. Team 5  (1 pt, goals 1)  20. Team 10 (0 pts, GD -4)
 *
 * Table markers (ws3_tabelle_markierung, league 1):
 *   Places  1- 4  green  – "Champions League"
 *   Places 16-17  orange – "Relegation"
 *   Places 18-20  red    – "Relegation"
 *
 * Guests (role guest/user) can browse the page, so no login is required.
 */

const LEAGUE_ID = 1;
const LEAGUE_URL = `/?page=leagues&id=${LEAGUE_ID}`;

/** Return the league-table <table> inside the leaguetable block (excludes
 *  the markers legend table which also has class "table"). */
function leagueTable(page: Page): Locator {
  return page.locator('#leaguetable_block table.table:not(.table-sm)');
}

test.describe('Leagues page – league table', () => {

  test('shows the page title and league selector with both leagues', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    // page title comes from the "league_navlabel" message = "Table".
    await expect(page.locator('h1')).toHaveText('Table');

    const select = page.locator('#id');
    await expect(select).toBeVisible();
    const options = select.locator('option');
    // first option is the empty placeholder, then 2 leagues ordered by country.
    // Countries sorted alphabetically: Deutschland < England.
    await expect(options).toHaveCount(3);
    await expect(options.nth(1)).toHaveText('Deutschland - Demo Bundesliga');
    await expect(options.nth(2)).toHaveText('England - Premier Sample League');
    // League 1 is pre-selected via the id query parameter.
    await expect(select).toHaveValue(String(LEAGUE_ID));
  });

  test('renders 20 teams in the standings', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    const table = leagueTable(page);
    await expect(table).toBeVisible();
    await expect(table.locator('tbody tr')).toHaveCount(20);
  });

  test('ranks Team 9 first and Team 10 last', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    const rows = leagueTable(page).locator('tbody tr');
    const firstRow = rows.nth(0);
    const lastRow = rows.nth(19);

    // First place: Team 9 (3 pts, GD +4, GF 4).
    await expect(firstRow.locator('td').nth(0)).toHaveText('1');
    await expect(firstRow.locator('td').nth(1)).toContainText('Team 9');
    await expect(firstRow.locator('td').nth(8)).toHaveText('3'); // points

    // Last place: Team 10 (0 pts, GD -4).
    await expect(lastRow.locator('td').nth(0)).toHaveText('20');
    await expect(lastRow.locator('td').nth(1)).toContainText('Team 10');
    await expect(lastRow.locator('td').nth(8)).toHaveText('0'); // points
  });

  test('shows correct stats for Team 9', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    const rows = leagueTable(page).locator('tbody tr');
    const team9Row = rows.nth(0); // Team 9 is first

    // Columns: P | Club | M | W | D | L | Goals | GD | Points
    await expect(team9Row.locator('td').nth(2)).toHaveText('1');  // matches
    await expect(team9Row.locator('td').nth(3)).toHaveText('1');  // wins
    await expect(team9Row.locator('td').nth(4)).toHaveText('0');  // draws
    await expect(team9Row.locator('td').nth(5)).toHaveText('0');  // losses
    await expect(team9Row.locator('td').nth(6)).toHaveText('4:0'); // goals
    await expect(team9Row.locator('td').nth(7)).toHaveText('4');  // GD
    await expect(team9Row.locator('td').nth(8)).toHaveText('3');  // points
  });

  test('shows correct stats for Team 5 (draw)', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    const rows = leagueTable(page).locator('tbody tr');
    // Team 5 is 10th (1 pt, GD 0, goals 1).
    const team5Row = rows.nth(9);

    await expect(team5Row.locator('td').nth(0)).toHaveText('10');
    await expect(team5Row.locator('td').nth(1)).toContainText('Team 5');
    await expect(team5Row.locator('td').nth(3)).toHaveText('0');  // wins
    await expect(team5Row.locator('td').nth(4)).toHaveText('1');  // draws
    await expect(team5Row.locator('td').nth(5)).toHaveText('0');  // losses
    await expect(team5Row.locator('td').nth(6)).toHaveText('1:1'); // goals
    await expect(team5Row.locator('td').nth(7)).toHaveText('0');  // GD
    await expect(team5Row.locator('td').nth(8)).toHaveText('1');  // points
  });

  test('displays table markers legend', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    // The marker legend table appears below the standings table.
    const markerTable = page.locator('#leaguetable_block h5 + table');
    await expect(markerTable).toBeVisible();

    const markerRows = markerTable.locator('tbody tr');
    await expect(markerRows).toHaveCount(3);
    await expect(markerTable).toContainText('Champions League');
    await expect(markerTable).toContainText('Relegation');
  });

  test('applies marker background colour to Champions League positions', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    const rows = leagueTable(page).locator('tbody tr');

    // Positions 1-4 should have a green background (Champions League).
    // The colour is applied via the CSSOM (el.style.backgroundColor), so the
    // browser may normalise #00FF00 to rgb(0, 255, 0) in the style attribute.
    for (let i = 0; i < 4; i++) {
      const placeCell = rows.nth(i).locator('td').nth(0);
      await expect(placeCell).toHaveAttribute('style',
        /background-color:\s*(?:#?00ff00|rgb\(\s*0\s*,\s*255\s*,\s*0\s*\))/i);
    }

    // Position 5 should NOT have a background colour.
    const pos5Cell = rows.nth(4).locator('td').nth(0);
    await expect(pos5Cell).not.toHaveAttribute('style', /background-color/i);
  });

  test('team name links to the team page', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    const rows = leagueTable(page).locator('tbody tr');
    // Click the team name link in the first row (Team 9).
    await rows.nth(0).locator('td').nth(1).getByRole('link', { name: 'Team 9' }).click();

    await expect(page.locator('h1')).toContainText('Team 9');
  });

  test('switching to League 2 renders its teams', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    // Select "Demo Bundesliga" (value 2) and submit.
    await page.selectOption('#id', '2');
    await page.locator('#pagecontent button[type=submit]').click();

    // The table reloads via AJAX into #leaguetable_block.
    const table = leagueTable(page);
    await expect(table.locator('tbody tr')).toHaveCount(20);
    await expect(table).toContainText('Team 21');
    await expect(table).toContainText('Team 40');
  });
});
