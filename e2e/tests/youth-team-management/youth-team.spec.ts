import { expect, test } from '@playwright/test';
import { CURRENCY, loginAs, loginAsUser1, youthPlayerRow } from './_helpers';

/**
 * E2E: the "My Youth Team" page – listing youth players, action dropdowns,
 * statistics, and the scout button.
 *
 * Spec files run in alphabetical order, so the action specs (youth-buy,
 * youth-fire, youth-makeprofessional, youth-scouting, youth-sell) have already
 * mutated Team 1's squad by the time this spec runs:
 *   - "Youth Striker" (fired) and "Young Goalie" (made professional) are gone.
 *   - "Young Talent" has been sold (transfer_fee > 0, no sell link).
 *   - "Buyable Youth" has been bought from Team 3 (now in Team 1).
 *   - A new scouted player may have been added (name is random).
 *
 * This spec therefore verifies stable, untouched players and structural
 * properties rather than exact row counts.
 */

test.describe.serial('My Youth Team page (logged in as user1)', () => {
  test('renders the page title and scout button', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');
    await expect(page.locator('h1')).toHaveText('My Youth Team');

    await expect(page.getByTestId('youth-scout-button')).toBeVisible();
    await expect(page.getByTestId('youth-scout-button')).toContainText('Scout for new talents');
  });

  test('lists youth players in a table', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    const table = page.getByTestId('youth-players-table');
    await expect(table).toBeVisible();

    // Untouched players must still be present.
    await expect(youthPlayerRow(page, 'Youth Midfielder')).toBeVisible();
    await expect(youthPlayerRow(page, 'Teen Defender')).toBeVisible();
    // Fired / promoted players must be gone.
    await expect(youthPlayerRow(page, 'Youth Striker')).toHaveCount(0);
    await expect(youthPlayerRow(page, 'Young Goalie')).toHaveCount(0);
  });

  test('shows salary derived from strength', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    // Youth Midfielder: strength 48, salary_per_strength 50 → 2 400 EUR.
    const row = youthPlayerRow(page, 'Youth Midfielder');
    await expect(row).toContainText(`2 400 ${CURRENCY}`);
  });

  test('marks players on the market with a remove-from-market link', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    // Market Midfielder was seeded on the market and is untouched by action specs.
    const row = youthPlayerRow(page, 'Market Midfielder');
    await expect(row.getByTestId('youth-player-removefrommarket')).toBeVisible();
    // Young Talent was sold by the sell spec → now also on the market.
    const soldRow = youthPlayerRow(page, 'Young Talent');
    await expect(soldRow.getByTestId('youth-player-removefrommarket')).toBeVisible();
  });

  test('expandable statistics show match stats', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    // Youth Midfielder: st_matches=4, st_goals=1, st_assists=1.
    const row = youthPlayerRow(page, 'Youth Midfielder');
    await expect(row).toContainText('4');

    const stats = row.getByTestId('youth-player-statistics');
    await expect(stats).not.toBeVisible();

    await row.getByTestId('youth-player-statistics-toggle').click();
    await expect(stats).toBeVisible();
    await expect(stats).toContainText('1'); // st_goals
  });

  test('action dropdown contains sell, fire, make-professional for a transferable player', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    // Youth Midfielder: age 16, transfer_fee=0, untouched by action specs.
    const row = youthPlayerRow(page, 'Youth Midfielder');
    await row.getByTestId('youth-player-actions-toggle').click();

    const menu = row.getByTestId('youth-player-actions-menu');
    await expect(menu).toBeVisible();
    await expect(menu.getByTestId('youth-player-sell')).toBeVisible();
    await expect(menu.getByTestId('youth-player-fire')).toBeVisible();
    await expect(menu.getByTestId('youth-player-makeprofessional')).toBeVisible();
  });

  test('action dropdown hides sell for a player already on the market', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    const row = youthPlayerRow(page, 'Market Midfielder');
    await row.getByTestId('youth-player-actions-toggle').click();

    const menu = row.getByTestId('youth-player-actions-menu');
    await expect(menu).toBeVisible();
    await expect(menu.getByTestId('youth-player-sell')).toHaveCount(0);
    await expect(menu.getByTestId('youth-player-fire')).toBeVisible();
    // Age 16 → can be made professional.
    await expect(menu.getByTestId('youth-player-makeprofessional')).toBeVisible();
  });

  test('action dropdown hides make-professional for under-age players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-team');

    // Teen Defender: age 15, min age for professional is 16.
    const row = youthPlayerRow(page, 'Teen Defender');
    await row.getByTestId('youth-player-actions-toggle').click();

    const menu = row.getByTestId('youth-player-actions-menu');
    await expect(menu).toBeVisible();
    await expect(menu.getByTestId('youth-player-makeprofessional')).toHaveCount(0);
    await expect(menu.getByTestId('youth-player-sell')).toBeVisible();
    await expect(menu.getByTestId('youth-player-fire')).toBeVisible();

    // The age cell shows an asterisk for under-age players.
    await expect(row).toContainText('15 *');
  });

  test('empty youth team shows an empty-state message', async ({ page }) => {
    // user4 manages Team 4 which has no youth players.
    await loginAs(page, 'user4', 'user4');
    await page.goto('/?page=youth-team');
    await expect(page.locator('h1')).toHaveText('My Youth Team');
    await expect(page.getByTestId('youth-players-table')).toHaveCount(0);
    await expect(page.getByText('There are currently no players in your youth team.')).toBeVisible();
  });
});
