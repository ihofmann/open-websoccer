import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Finances" page.
 *
 * Seed data: Team 1 has budget 5 000 000 and receives sponsor payments from
 * "Sponsor Alpha" (100 000 per match).
 *
 * The exact budget amount is deliberately not asserted: the admin center CRUD
 * suite books a test transaction for this team, so the budget depends on which
 * specs ran before.
 */

test('finances page shows budget and account statement', async ({ page }) => {
  await loginAsUser1(page);
  await page.goto('/?page=finances');
  await expect(page.locator('h1')).toHaveText('Finances');

  const content = page.locator('#pagecontent');
  await expect(content.locator('#budgetHeading')).toHaveText(
    new RegExp(`^Budget: [\\d ]+ ${CURRENCY}$`),
  );

  const statement = content.locator('#accountStatementTable');
  await expect(statement.locator('thead th').nth(1)).toHaveText('Sender / Recipient');
  const row = statement.locator('tbody tr', { hasText: 'Sponsor Alpha' });
  await expect(row).toContainText('Sponsor payment');
  await expect(row).toContainText(`100 000 ${CURRENCY}`);
});
