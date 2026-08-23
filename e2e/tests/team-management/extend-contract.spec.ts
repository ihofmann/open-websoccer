import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1, submitContractOffer } from './_helpers';

/**
 * E2E: the "Extend Contract" page.
 *
 * Seed data: Player id 22 (Player1_MS2 Lastname112) belongs to Team 1, with
 * salary 50 000, goal bonus 1 000, contract 30 matches, satisfaction 60.
 *
 * The minimum acceptable salary is 70 000 (current 50 000 * satisfaction factor).
 * The minimum contract length is 20 matches. The goal bonus must exceed
 * 1 000 * 1.3 = 1 300.
 */

test.describe.serial('Extend contract page (logged in as user1)', () => {
  test('shows the existing contract and the offer form', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');
    await expect(page.locator('h1')).toHaveText('Negotiation with Player1_MS2 Lastname112');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Existing Contract');
    await expect(content).toContainText(`50 000 ${CURRENCY}`);
    await expect(content).toContainText('30 matches');
    await expect(content).toContainText(
      "Note that the player's satisfaction will decrease for each offer that is too low.",
    );
    await expect(content).toContainText('Your Offer');

    await expect(page.locator('#salary')).toHaveAttribute('required', '');
    await expect(page.locator('#matches')).toHaveAttribute('required', '');
    await expect(page.locator('#goal_bonus')).not.toHaveAttribute('required', '');
  });

  test('form reports a contract length below the minimum', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    await page.locator('#salary').fill('200000');
    await page.locator('#goal_bonus').fill('2000');
    await page.locator('#matches').fill('10');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    // Not scoped to #pagecontent on purpose, see submitContractOffer().
    await expect(page.locator('.invalid-feedback')).toContainText('must be at least 20.');
  });

  test('form rejects a salary the player will not accept', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    // Current salary is 50 000 and satisfaction 60, so the player asks for
    // at least 70 000 per match.
    await page.locator('#salary').fill('55000');
    await page.locator('#goal_bonus').fill('2000');
    await page.locator('#matches').fill('30');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-danger')).toContainText(
      'The offered salary is too low for the player.',
    );
  });

  test('form rejects a goal bonus below the current one', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    // Goal bonus must exceed 1 000 * 1.3.
    await page.locator('#salary').fill('200000');
    await page.locator('#goal_bonus').fill('1000');
    await page.locator('#matches').fill('30');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-danger')).toContainText(
      'The player wants a higher goal bonus.',
    );
  });

  test('saves the new conditions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    await page.locator('#salary').fill('200000');
    await page.locator('#goal_bonus').fill('5000');
    await page.locator('#matches').fill('40');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-success')).toContainText(
      'The contract has been successfully extended under the new conditions!',
    );
    // The offer form is replaced by a link back to the squad, and the
    // "existing contract" summary shows the new conditions.
    const content = page.locator('#pagecontent');
    await expect(content.getByRole('link', { name: 'My Team' })).toBeVisible();
    await expect(content).toContainText(`200 000 ${CURRENCY}`);
    await expect(content).toContainText(`5 000 ${CURRENCY}`);
    await expect(content).toContainText('40 matches');

    await page.goto('/?page=myteam');
    const row = page.locator('#playerTable tbody tr', {
      hasText: 'Player1_MS2 Lastname112',
    });
    await expect(row).toContainText(`200 000 ${CURRENCY}`);
    await expect(row.locator('td').last()).toContainText('40');
  });
});
