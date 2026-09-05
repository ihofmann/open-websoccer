import { expect, test } from '@playwright/test';
import { loginAsUser1 } from './_helpers';

/**
 * E2E: the "Transfer Market" page.
 *
 * Seed data + state from earlier specs: Player1_RS1 Lastname121 is listed on
 * the transfer market (put there by the sell-player spec).
 */

test.describe('Transfer market page (logged in as user1)', () => {
  test('position filter narrows the list', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transfermarket');
    await expect(page.locator('h1')).toHaveText('Transfer Market');

    await expect(page.locator('#transferTable')).toContainText('Player1_RS1 Lastname121');

    // The listed player is a forward, so filtering for goalkeepers finds nothing.
    // The selection applies immediately on change (no "Display" button anymore).
    await page.selectOption('#position', 'goaly');
    await expect(page.locator('#pagecontent')).toContainText(
      'Could not find any players on the transfer market.',
    );

    await page.selectOption('#position', 'striker');
    await expect(page.locator('#transferTable')).toContainText('Player1_RS1 Lastname121');

    await page.getByRole('link', { name: 'Reset' }).click();
    await expect(page.locator('#position')).toHaveValue('');
  });

  test('tabs load their content via AJAX', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transfermarket');

    await expect(page.locator('#mybidslist')).toBeEmpty();

    const [response] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('ajax.php') && r.url().includes('mybids')),
      page.locator('#transferTab a', { hasText: 'My Bids' }).click(),
    ]);
    expect(response.ok()).toBe(true);

    await expect(page.locator('#mybids')).toBeVisible();
    await expect(page.locator('#mybidslist')).not.toBeEmpty();
  });
});
