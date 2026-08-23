import { expect, test } from '@playwright/test';
import { loginAsUser1, PLAYER_IDS, youthPlayerRow } from './_helpers';

/**
 * E2E: the "Buy Youth Player" page – purchasing a transferable youth player
 * from another team.
 *
 * Seed data: Player ID 25 "Buyable Youth" belongs to Team 3, transfer_fee
 * 150 000.  Team 1 (user1) has a budget of 5 000 000.
 *
 * This spec is serial: the confirmation test transfers the player.
 */

test.describe.serial('Buy youth player page (logged in as user1)', () => {
  test('shows the confirmation prompt with player details', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-buy&id=${PLAYER_IDS.buyable}`);

    await expect(page.locator('h1')).toHaveText('Buy youth player');

    const details = page.getByTestId('youth-player-details');
    await expect(details).toBeVisible();
    await expect(details).toContainText('Buyable Youth');
    // Transfer fee 150 000.
    await expect(details).toContainText('150 000');

    await expect(page.getByTestId('youth-buy-form')).toBeVisible();
    await expect(page.getByTestId('youth-buy-confirm')).toBeVisible();
    await expect(page.getByTestId('youth-buy-cancel')).toBeVisible();

    await expect(page.locator('#pagecontent')).toContainText(
      'Do you really want to buy this player?',
    );
  });

  test('confirming buys the player and adds them to the youth team', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-buy&id=${PLAYER_IDS.buyable}`);

    await page.getByTestId('youth-buy-confirm').click();

    // The controller forwards to the youth-team page.
    await expect(page.locator('h1')).toHaveText('My Youth Team');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player belongs to your team now!',
    );

    // The bought player now appears in Team 1's youth squad.
    await expect(youthPlayerRow(page, 'Buyable Youth')).toBeVisible();
  });

  test('cancel link returns to the marketplace', async ({ page }) => {
    // After buying, the player is no longer on the market. Use a different
    // buyable player (ID 26 "Market Keeper", Team 2) to test the cancel link.
    await loginAsUser1(page);
    await page.goto('/?page=youthplayer-buy&id=26');

    await page.getByTestId('youth-buy-cancel').click();
    await expect(page.locator('h1')).toHaveText('Marketplace');
  });
});
