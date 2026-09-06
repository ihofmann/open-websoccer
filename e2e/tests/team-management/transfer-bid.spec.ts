import { expect, test } from '@playwright/test';
import { loginAsUser1 } from './_helpers';

/**
 * E2E: bidding on a free agent on the transfer market.
 *
 * Regression test: bidding on a player WITHOUT a club used to crash with
 * "Fehler Field 'abloese' doesn't have a default value", because the bid form
 * only submits hand money (there is no transfer fee) and the controller wrote
 * the missing fee into the NOT NULL `abloese` column as DEFAULT.
 *
 * Seed data: player id 973 ("Free Agent") is a midfielder without a club on
 * the transfer market (see e2e/seed/seed_data.sql). The auction ends a week
 * after seeding, so it is still open when the suite runs.
 */

test.describe.serial('Transfer bid on a free agent (logged in as user1)', () => {
  test('offers hand money instead of a transfer fee for a player without a club', async ({
    page,
  }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transfermarket');

    const row = page.locator('#transferTable tbody tr', { hasText: 'Free Agent' });
    await expect(row).toContainText('without team');
    await row.getByRole('link', { name: 'Bid' }).click();

    await expect(page.locator('h1')).toHaveText('Bid for Free Agent');
    // A free agent has no transfer fee, so the form asks for hand money and
    // must not contain an amount (fee) field.
    await expect(page.locator('#handmoney')).toBeVisible();
    await expect(page.locator('#amount')).toHaveCount(0);
  });

  test('submits a bid without triggering the abloese database error', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transfer-bid&id=973');

    await expect(page.locator('#handmoney')).toBeVisible();

    await page.locator('#handmoney').fill('100000');
    await page.locator('#contract_salary').fill('60000');
    await page.locator('#contract_goal_bonus').fill('2000');
    await page.locator('#contract_matches').fill('30');

    await page.locator('form button.ajaxSubmit').click();

    // The bid is saved and the block re-renders with the new highest bid.
    await expect(page.locator('#bidmessages .alert-success')).toContainText(
      'Your bid has been successfully saved.',
    );
    await expect(page.locator('#bidmessages .alert-danger')).toHaveCount(0);
    await expect(page.locator('#transfer-bid_block')).toContainText('Existing Highest Bid');
  });
});
