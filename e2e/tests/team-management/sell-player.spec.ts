import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1, menuItem } from './_helpers';

/**
 * E2E: the "Sell Player" page – putting a player on the transfer market.
 *
 * Seed data: Player id 23 (Player1_RS1 Lastname121) belongs to Team 1, has a
 * market value of 725 000, salary 50 000, and a 30-match contract.
 */

test.describe.serial('Sell player page (logged in as user1)', () => {
  test('shows contract details and the minimum bid field', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');
    await expect(page.locator('h1')).toHaveText('Sell Player1_RS1 Lastname121');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Put on Transfer List');
    await expect(content).toContainText('Market value');
    await expect(content).toContainText(`725 000 ${CURRENCY}`);
    await expect(content).toContainText(`50 000 ${CURRENCY}`);
    await expect(content).toContainText('30 matches');
    await expect(content.getByRole('link', { name: 'To Player Profile' })).toBeVisible();

    await expect(page.locator('#min_bid')).toBeVisible();
    await expect(page.locator('#min_bid')).toHaveAttribute('required', '');
  });

  test('form requires a minimum bid', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');

    await page.locator('#min_bid').fill('');
    expect(
      await page.locator('#min_bid').evaluate((el) => (el as HTMLInputElement).checkValidity()),
    ).toBe(false);

    await page.locator('#sellPlayerForm button[type=submit]').click();

    // Native validation blocked the submit, so we stay on the same page.
    await expect(page.locator('h1')).toHaveText('Sell Player1_RS1 Lastname121');
    await expect(page.locator('#messages .alert-success')).toHaveCount(0);
  });

  test('form rejects a bid below half the market value', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');

    // Market value is 725 000, so anything below 362 500 is rejected.
    await page.locator('#min_bid').fill('1000');
    await page.locator('#sellPlayerForm button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText(
      "The minimum bid must be at least half of the player's market value.",
    );
    await expect(page.locator('h1')).toHaveText('Sell Player1_RS1 Lastname121');
  });

  test('form puts the player on the transfer market', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');

    await page.locator('#min_bid').fill('500000');
    await page.locator('#sellPlayerForm button[type=submit]').click();

    // The controller forwards to the transfer market.
    await expect(page.locator('h1')).toHaveText('Transfer Market');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player is now available at the transfer market.',
    );
    await expect(page.locator('#transferTable')).toContainText('Player1_RS1 Lastname121');

    // My Players marks him with the transfer market icon and hides "Sell".
    await page.goto('/?page=myteam');
    const row = page.locator('#playerTable tbody tr', { hasText: 'Player1_RS1 Lastname121' });
    await expect(row.getByTestId('transfermarket-marker')).toBeVisible();
    await row.getByTestId('player-actions-toggle').click();
    await expect(menuItem(row.getByTestId('player-actions-menu'), 'Sell')).toHaveCount(0);
  });
});
