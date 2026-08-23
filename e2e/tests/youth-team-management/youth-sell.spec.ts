import { expect, test } from '@playwright/test';
import { loginAsUser1, PLAYER_IDS, youthPlayerRow } from './_helpers';

/**
 * E2E: the "Sell Youth Player" page – offering a youth player in the public
 * marketplace by setting a transfer fee.
 *
 * Seed data: Player ID 1 "Young Talent" belongs to Team 1, age 16,
 * strength 50, transfer_fee = 0 (not yet on market).
 *
 * This spec is serial: the second test submits the form and mutates state.
 */

test.describe.serial('Sell youth player page (logged in as user1)', () => {
  test('shows player details and the transfer fee form', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-sell&id=${PLAYER_IDS.sell}`);

    await expect(page.locator('h1')).toHaveText('Offer youth player in public market');

    // The player details block is rendered above the form.
    const details = page.getByTestId('youth-player-details');
    await expect(details).toBeVisible();
    await expect(details).toContainText('Young Talent');
    await expect(details).toContainText('Midfield');

    // The form with the transfer fee input.
    await expect(page.getByTestId('youth-sell-form')).toBeVisible();
    const feeInput = page.getByTestId('youth-sell-transfer-fee');
    await expect(feeInput).toBeVisible();
    await expect(feeInput).toHaveAttribute('required', '');

    await expect(page.getByTestId('youth-sell-submit')).toBeVisible();
    await expect(page.getByTestId('youth-sell-cancel')).toBeVisible();
  });

  test('form requires a transfer fee', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-sell&id=${PLAYER_IDS.sell}`);

    // Leave the fee empty and submit.
    const feeInput = page.getByTestId('youth-sell-transfer-fee');
    await feeInput.fill('');

    expect(
      await feeInput.evaluate((el) => (el as HTMLInputElement).checkValidity()),
    ).toBe(false);

    await page.getByTestId('youth-sell-submit').click();

    // Native validation blocked the submit, so we stay on the sell page.
    await expect(page.locator('h1')).toHaveText('Offer youth player in public market');
    await expect(page.locator('#messages .alert-success')).toHaveCount(0);
  });

  test('submitting a valid transfer fee puts the player on the market', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-sell&id=${PLAYER_IDS.sell}`);

    await page.getByTestId('youth-sell-transfer-fee').fill('300000');
    await page.getByTestId('youth-sell-submit').click();

    // The controller forwards to the youth-team page.
    await expect(page.locator('h1')).toHaveText('My Youth Team');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player can now be bought by other managers.',
    );

    // The player now has a remove-from-market link (transfer_fee > 0).
    const row = youthPlayerRow(page, 'Young Talent');
    await expect(row.getByTestId('youth-player-removefrommarket')).toBeVisible();
  });

  test('cancel link returns to the youth team page', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-sell&id=${PLAYER_IDS.sell}`);

    await page.getByTestId('youth-sell-cancel').click();
    await expect(page.locator('h1')).toHaveText('My Youth Team');
  });
});
