import { expect, test } from '@playwright/test';
import { loginAsUser1 } from './_helpers';

/**
 * E2E: the "Tickets" page – setting ticket prices.
 *
 * Seed data: Team 1 has a stadium with stands, seats, grand stands/seats and
 * VIP lounges. No tickets have been sold yet (last match sold: 0/...).
 */

const PRICE_FIELDS = ['#p_stands', '#p_seats', '#p_stands_grand', '#p_seats_grand', '#p_vip'];

test.describe.serial('Tickets page (logged in as user1)', () => {
  test('shows all price fields with the sold ratio', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');
    await expect(page.locator('h1')).toHaveText('Tickets');

    for (const id of PRICE_FIELDS) {
      await expect(page.locator(id)).toBeVisible();
      await expect(page.locator(id)).toHaveAttribute('required', '');
      await expect(page.locator(id)).not.toHaveValue('');
    }

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('V.I.P. Lounges');
    await expect(content).toContainText('Last match sold: 0/10 000');
    await expect(content).toContainText('Last match sold: 0/200');
  });

  test('form reports missing prices', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');

    for (const id of PRICE_FIELDS) {
      await page.locator(id).fill('');
    }
    await page.locator('#ticketsForm button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    await expect(page.locator('#pagecontent .invalid-feedback').first()).toContainText(
      'must be provided.',
    );
    await expect(page.locator('#messages .alert-success')).toHaveCount(0);
  });

  test('form reports prices above the allowed maximum', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');

    await page.locator('#p_vip').fill('5000');
    await page.locator('#ticketsForm button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    await expect(page.locator('#pagecontent .invalid-feedback')).toContainText(
      'must not be higher than 1000.',
    );
  });

  test('saves new prices', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');

    await page.locator('#p_stands').fill('11');
    await page.locator('#p_seats').fill('16');
    await page.locator('#p_stands_grand').fill('21');
    await page.locator('#p_seats_grand').fill('26');
    await page.locator('#p_vip').fill('61');
    await page.locator('#ticketsForm button[type=submit]').click();

    await expect(page.locator('#messages .alert-success')).toContainText('Successfully saved.');

    await page.goto('/?page=tickets');
    await expect(page.locator('#p_stands')).toHaveValue('11');
    await expect(page.locator('#p_vip')).toHaveValue('61');
  });
});
