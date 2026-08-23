import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Transfer Offers" page.
 *
 * Seed data: Team 2 has a pending transfer offer for Player1_T1 Lastname11
 * (amount 1 000 000).
 */

test.describe.serial('Transfer offers page (logged in as user1)', () => {
  test('lists the received offer', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transferoffers');
    await expect(page.locator('h1')).toHaveText('Transfer Offers');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Received Offers');
    const row = content.locator('#receivedOffersTable tbody tr', {
      hasText: 'Player1_T1 Lastname11',
    });
    await expect(row).toContainText('Team 2');
    await expect(row).toContainText(`1 000 000 ${CURRENCY}`);
    await expect(row.getByTestId('accept-offer')).toBeVisible();
    await expect(row.getByTestId('reject-offer')).toBeVisible();
  });

  test('a received offer can be rejected with a comment', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transferoffers');

    const row = page.locator('#receivedOffersTable tbody tr', {
      hasText: 'Player1_T1 Lastname11',
    });
    await row.getByTestId('reject-offer').click();

    const modal = page.getByTestId('reject-offer-modal');
    await expect(modal).toBeVisible();
    await expect(modal).toContainText('Reject offer');
    await expect(modal.locator('#allow_alternative')).toBeChecked();
    await modal.locator('#comment').fill('Not enough for my top scorer.');
    await modal.getByRole('button', { name: 'Submit' }).click();

    await expect(page.locator('#messages .alert-success')).toContainText(
      'The offer has been successfully rejected.',
    );
    await expect(page.locator('#pagecontent')).toContainText(
      'You have not received any offers from other managers yet.',
    );
  });
});
