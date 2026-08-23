import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1, menuItem } from './_helpers';

/**
 * E2E: the "My Players" page – squad overview with per-player actions.
 *
 * Seed data: Team 1 has 24 players, all with salary 50 000, contract 30
 * matches, strength 75, satisfaction 60. Captain is not yet assigned.
 *
 * This spec is serial: later tests build on state created by earlier ones
 * (captain, unsellable flag, player on the transfer market).
 */

test.describe.serial('My players page (logged in as user1)', () => {
  test('lists the whole squad with salary total', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');
    await expect(page.locator('h1')).toHaveText('My Players');

    const table = page.locator('#playerTable');
    await expect(table.locator('tbody tr')).toHaveCount(24);

    const headers = table.locator('thead th');
    await expect(headers.nth(1)).toHaveText('Name');
    await expect(headers.nth(4)).toContainText('Salary per Match');

    await expect(table).toContainText('Player1_T1 Lastname11');
    await expect(table).toContainText('Player1_RS2 Lastname122');

    // The tfoot renders the salary sum with the currency. The exact amount is
    // not asserted because the extend-contract spec (which runs first in
    // alphabetical order) changes one player's salary from 50 000 to 200 000.
    await expect(table.locator('tfoot')).toContainText(new RegExp(`[\\d ]+ ${CURRENCY}`));
  });

  test('player action dropdown offers all enabled squad actions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const firstRow = page.locator('#playerTable tbody tr').first();
    await firstRow.getByTestId('player-actions-toggle').click();

    const menu = firstRow.getByTestId('player-actions-menu');
    await expect(menu).toBeVisible();
    for (const label of [
      'Sell',
      'Mark as unsellable',
      'Offer to borrow',
      'Nominate as captain',
      'Dismiss',
    ]) {
      await expect(menuItem(menu, label)).toBeVisible();
    }

    // Contracts run for 30 matches, which is above
    // contract_max_number_of_remaining_matches (15), so no extension offer yet.
    await expect(menu).not.toContainText('Contract Extension');
  });

  test('dismiss action opens a confirmation modal instead of firing directly', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const firstRow = page.locator('#playerTable tbody tr').first();
    await firstRow.getByTestId('player-actions-toggle').click();
    await menuItem(firstRow.getByTestId('player-actions-menu'), 'Dismiss').click();

    // The modal is inside the same row as the dropdown.
    const modal = firstRow.getByTestId('fire-player-modal');
    await expect(modal).toBeVisible();
    await expect(modal).toContainText('Player1_T1 Lastname11');
    await modal.getByRole('button', { name: 'Cancel' }).click();
    await expect(modal).toBeHidden();

    // Squad is untouched.
    await expect(page.locator('#playerTable tbody tr')).toHaveCount(24);
  });

  test('nominating a captain marks the player with the captain icon', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const firstRow = page.locator('#playerTable tbody tr').first();
    await firstRow.getByTestId('player-actions-toggle').click();
    await menuItem(firstRow.getByTestId('player-actions-menu'), 'Nominate as captain').click();

    await expect(page.locator('#messages .alert-success')).toContainText(
      'Your new captain has been successfully selected!',
    );

    const captainRow = page.locator('#playerTable tbody tr', {
      hasText: 'Player1_T1 Lastname11',
    });
    await expect(captainRow.getByTestId('captain-marker')).toBeVisible();

    // The captain can no longer be nominated again.
    await captainRow.getByTestId('player-actions-toggle').click();
    await expect(captainRow.getByTestId('player-actions-menu')).not.toContainText(
      'Nominate as captain',
    );
  });

  test('marking a player unsellable is applied and can be reverted', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const row = page.locator('#playerTable tbody tr', { hasText: 'Player1_T2 Lastname12' });
    await row.getByTestId('player-actions-toggle').click();
    await menuItem(row.getByTestId('player-actions-menu'), 'Mark as unsellable').click();

    await expect(page.locator('#messages .alert-success')).toContainText(
      'Player has been marked as unsellable.',
    );

    // The AJAX response re-rendered #pagecontent, so re-resolve the row.
    const markedRow = page.locator('#playerTable tbody tr', {
      hasText: 'Player1_T2 Lastname12',
    });
    // The unsellable marker link is visible next to the player name.
    await expect(markedRow.getByTestId('unmark-unsellable')).toBeVisible();
    // Selling is no longer offered for an unsellable player.
    await markedRow.getByTestId('player-actions-toggle').click();
    await expect(menuItem(markedRow.getByTestId('player-actions-menu'), 'Sell')).toHaveCount(0);

    // Revert via the marker link next to the player name.
    await markedRow.getByTestId('unmark-unsellable').click();
    await expect(page.locator('#messages .alert-success')).toContainText(
      'Unsellable flag has been removed.',
    );
    await expect(
      page
        .locator('#playerTable tbody tr', { hasText: 'Player1_T2 Lastname12' })
        .getByTestId('unmark-unsellable'),
    ).toHaveCount(0);
  });
});
