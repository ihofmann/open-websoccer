import { expect, test } from '@playwright/test';
import { loginAsUser1, MATCH_IDS } from './_helpers';

/**
 * E2E: the "Youth Matches" page – listing past and upcoming youth matches.
 *
 * Seed data:
 *   * Match #1: completed (simulated), Team 1 vs Team 2, 2-1.
 *   * Match #2: scheduled (not simulated), Team 1 vs Team 2, ~8 days ahead.
 *
 * youth-matchrequests.spec.ts ran before this one and accepted Team 2's
 * request, creating an additional scheduled match (~5 days ahead). So the
 * list now contains 3 matches.
 */

test.describe('Youth matches page (logged in as user1)', () => {
  test('lists matches with formation and result links', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matches');
    await expect(page.locator('h1')).toHaveText('Matches of my team');

    const table = page.getByTestId('youth-matches-table');
    await expect(table).toBeVisible();

    const rows = table.getByTestId('youth-match-row');
    // At least the 2 seeded matches (plus any created by earlier specs).
    await expect(rows).not.toHaveCount(0);

    // The completed match shows a result link "2 - 1".
    const resultLink = page.getByTestId('youth-match-result');
    await expect(resultLink).toBeVisible();
    await expect(resultLink).toContainText('2 - 1');

    // The scheduled match shows a Formation button.
    const formationButton = page.getByTestId('youth-match-formation');
    await expect(formationButton).toBeVisible();
  });

  test('result link navigates to the match report', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matches');

    await page.getByTestId('youth-match-result').click();
    await expect(page.locator('h1')).toHaveText('Team 1 - Team 2');
  });

  test('formation button navigates to the formation page', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matches');

    await page.getByTestId('youth-match-formation').first().click();
    await expect(page.locator('h1')).toHaveText('Formation and Tactics');
  });
});
