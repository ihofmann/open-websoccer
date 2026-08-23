import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Training" page and the trainer-details hire form.
 *
 * Seed data: Team 1 can hire trainer "Coach Carl" (salary 50 000 per unit).
 */

test.describe('Training page (logged in as user1)', () => {
  test('lists hireable trainers', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=training');
    await expect(page.locator('h1')).toHaveText('Training');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Open Training Units');
    await expect(content).toContainText('You can execute another training unit every 24 hours.');
    await expect(content).toContainText('Choose a Trainer');

    const table = content.locator('#trainersTable');
    const headers = table.locator('thead th');
    await expect(headers.nth(1)).toHaveText('Salary per Unit');
    await expect(headers.nth(2)).toHaveText('Effectiveness: Technique Training');

    const trainerRow = table.locator('tbody tr', { hasText: 'Coach Carl' });
    await expect(trainerRow).toContainText(`50 000 ${CURRENCY}`);
    await expect(trainerRow.getByRole('link', { name: 'Choose' })).toBeVisible();
  });

  test('choosing a trainer opens the hire form', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=training');

    await page
      .locator('#trainersTable tbody tr', { hasText: 'Coach Carl' })
      .getByRole('link', { name: 'Choose' })
      .click();

    await expect(page).toHaveURL(/page=trainer-details/);
    await expect(page.locator('h1')).toHaveText('Coach Carl');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Hire This Trainer');
    await expect(content).toContainText('Effectiveness: Stamina Training');
    await expect(page.locator('#units')).toBeVisible();
    // Number of units is mandatory.
    await expect(page.locator('#units')).toHaveAttribute('required', '');
    expect(await page.locator('#units').evaluate((el) => (el as HTMLInputElement).checkValidity()))
      .toBe(false);
  });
});
