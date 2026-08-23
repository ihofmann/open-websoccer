import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Youth Marketplace" page – browsing transferable youth players,
 * filtering by position, and the buy / remove-from-market actions.
 *
 * Seed data:
 *   * Player 4  "Market Midfielder"  Team 1  Mittelfeld  fee 200 000 (own).
 *   * Player 25 "Buyable Youth"       Team 3  Sturm       fee 150 000.
 *   * Player 26 "Market Keeper"       Team 2  Torwart     fee 120 000.
 *
 * Note: youth-buy.spec.ts runs before this spec (alphabetical order) and has
 * already bought "Buyable Youth" (now belongs to Team 1, transfer_fee = 0).
 * The marketplace therefore lists "Market Midfielder" and "Market Keeper".
 */

test.describe('Youth marketplace page (logged in as user1)', () => {
  test('lists transferable youth players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-marketplace');
    await expect(page.locator('h1')).toHaveText('Marketplace');

    const table = page.getByTestId('youth-marketplace-table');
    await expect(table).toBeVisible();

    // Market Midfielder (own player, still on market).
    await expect(table).toContainText('Market Midfielder');
    // Market Keeper (Team 2 player on market).
    await expect(table).toContainText('Market Keeper');
  });

  test('shows remove-from-market for own players and buy for others', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-marketplace');

    const table = page.getByTestId('youth-marketplace-table');

    // Own player (Market Midfielder, Team 1): remove-from-market, no buy.
    const ownRow = table.getByTestId('youth-marketplace-row').filter({ hasText: 'Market Midfielder' });
    await expect(ownRow.getByTestId('youth-marketplace-removefrommarket')).toBeVisible();
    await expect(ownRow.getByTestId('youth-marketplace-buy')).toHaveCount(0);

    // Other team's player (Market Keeper, Team 2): buy button, no remove.
    const otherRow = table.getByTestId('youth-marketplace-row').filter({ hasText: 'Market Keeper' });
    await expect(otherRow.getByTestId('youth-marketplace-buy')).toBeVisible();
    await expect(otherRow.getByTestId('youth-marketplace-removefrommarket')).toHaveCount(0);
  });

  test('position filter narrows the list', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-marketplace');

    const table = page.getByTestId('youth-marketplace-table');

    // No filter: both Mittelfeld and Torwart players visible.
    await expect(table.getByTestId('youth-marketplace-row')).toHaveCount(2);

    // Filter by Torwart → only Market Keeper.
    await page.getByTestId('youth-marketplace-position').selectOption('Torwart');
    await page.getByTestId('youth-marketplace-filter-submit').click();
    await expect(table.getByTestId('youth-marketplace-row')).toHaveCount(1);
    await expect(table).toContainText('Market Keeper');
    await expect(table).not.toContainText('Market Midfielder');

    // Reset filter.
    await page.getByTestId('youth-marketplace-filter-reset').click();
    await expect(table.getByTestId('youth-marketplace-row')).toHaveCount(2);
  });

  test('filter by Sturm finds no players after buy', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-marketplace');

    // Buyable Youth (Sturm) has been bought by the earlier buy spec.
    await page.getByTestId('youth-marketplace-position').selectOption('Sturm');
    await page.getByTestId('youth-marketplace-filter-submit').click();

    await expect(page.locator('#pagecontent')).toContainText(
      'No youth players are for sale at the moment.',
    );
  });

  test('transfer fee and salary are displayed', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-marketplace');

    const table = page.getByTestId('youth-marketplace-table');
    const row = table.getByTestId('youth-marketplace-row').filter({ hasText: 'Market Keeper' });

    // Transfer fee 120 000, salary = strength 50 * 50 = 2 500.
    await expect(row).toContainText(`120 000 ${CURRENCY}`);
    await expect(row).toContainText(`2 500 ${CURRENCY}`);
  });
});
