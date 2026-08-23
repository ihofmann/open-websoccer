import { expect, test } from '@playwright/test';
import { loginAsUser1, PLAYER_IDS, youthPlayerRow } from './_helpers';

/**
 * E2E: the "Fire Youth Player" page – dismissing (deleting) a youth player.
 *
 * Seed data: Player ID 5 "Youth Striker" belongs to Team 1, age 17, Sturm.
 *
 * This spec is serial: the confirmation test deletes the player.
 */

test.describe.serial('Fire youth player page (logged in as user1)', () => {
  test('shows the confirmation prompt with player details', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-fire&id=${PLAYER_IDS.fire}`);

    await expect(page.locator('h1')).toHaveText('Dismiss youth player');

    const details = page.getByTestId('youth-player-details');
    await expect(details).toBeVisible();
    await expect(details).toContainText('Youth Striker');

    await expect(page.getByTestId('youth-fire-form')).toBeVisible();
    await expect(page.getByTestId('youth-fire-confirm')).toBeVisible();
    await expect(page.getByTestId('youth-fire-cancel')).toBeVisible();

    // The confirmation message is rendered.
    await expect(page.locator('#pagecontent')).toContainText(
      'Do you really want to lay off this player?',
    );
  });

  test('confirming deletes the player and returns to youth team', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-fire&id=${PLAYER_IDS.fire}`);

    await page.getByTestId('youth-fire-confirm').click();

    // The controller forwards to the youth-team page.
    await expect(page.locator('h1')).toHaveText('My Youth Team');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player got dismissed.',
    );

    // The player is gone from the table.
    await expect(youthPlayerRow(page, 'Youth Striker')).toHaveCount(0);
  });

  test('cancel link returns to the youth team page', async ({ page }) => {
    // Use a different player that has not been fired yet.
    await loginAsUser1(page);
    await page.goto('/?page=youthplayer-fire&id=6');

    await page.getByTestId('youth-fire-cancel').click();
    await expect(page.locator('h1')).toHaveText('My Youth Team');
  });
});
