import { expect, test } from '@playwright/test';
import { loginAs, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Scouting" page – choosing a scout, selecting a country, and the
 * scouting break cooldown.
 *
 * Seed data:
 *   * Team 1 (user1): scouting_last_execution = 0 → scouting possible.
 *   * Team 2 (user2): scouting_last_execution = now → scouting blocked 24 h.
 *   * Scouts: #1 "Scout Sam" (fee 5 000), #2 "Scout Alex" (fee 3 000).
 *   * Scouting countries: England, Deutschland, Italien, Spanien (from
 *     admin/config/names/ folders).
 *
 * The scouting action is probabilistic (75 % success by default), so the
 * outcome test accepts either a success or a failure message.
 */

test.describe.serial('Scouting page', () => {
  test('shows last execution time and scout list when scouting is possible', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-scouting');
    await expect(page.locator('h1')).toHaveText('Scouting');

    // Team 1 never scouted before.
    await expect(page.locator('#pagecontent')).toContainText('Never executed before');

    // Scout selection table is visible.
    const table = page.getByTestId('scouts-table');
    await expect(table).toBeVisible();
    await expect(table.getByTestId('scout-row')).toHaveCount(2);
    await expect(table).toContainText('Scout Sam');
    await expect(table).toContainText('Scout Alex');
  });

  test('choosing a scout shows the country selection', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-scouting');

    // Click "Choose" on the first scout (Scout Sam).
    page.getByTestId('scouts-table').getByTestId('scout-choose').first().click();
    await page.waitForLoadState('networkidle');

    // The page now shows country buttons.
    await expect(page.locator('#pagecontent')).toContainText(
      'In which country should we search for new talents?',
    );
    const countryButtons = page.getByTestId('scout-country');
    await expect(countryButtons).toHaveCount(4);
    // England must be one of the options.
    await expect(page.getByTestId('scout-country').filter({ hasText: 'England' })).toBeVisible();
  });

  test('scouting a country produces a result message', async ({ page }) => {
    await loginAsUser1(page);
    // Go directly to the scouting page with scout pre-selected.
    await page.goto('/?page=youth-scouting&scoutid=1');
    await expect(page.getByTestId('scout-country')).toHaveCount(4);

    // Click the England country button.
    await page.getByTestId('scout-country').filter({ hasText: 'England' }).click();
    await page.waitForLoadState('networkidle');

    // The action redirects to either youth-team (success) or youth-scouting
    // (failure).  Both outcomes show an alert message.
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
    const heading = (await h1.textContent())?.trim();

    if (heading === 'My Youth Team') {
      // Scouting succeeded: a new talent was found.
      await expect(page.locator('#messages .alert-success')).toContainText(
        'Congratulations! The scout could find a new talent.',
      );
    } else {
      // Scouting failed: no talent found.
      await expect(page.locator('#messages .alert-warning')).toContainText(
        'Unfortunately, the scout has not found any talented player',
      );
    }
  });

  test('scouting is blocked during the cooldown period', async ({ page }) => {
    // Team 2 (user2) was just scouted (scouting_last_execution = now).
    await loginAs(page, 'user2', 'user2');
    await page.goto('/?page=youth-scouting');
    await expect(page.locator('h1')).toHaveText('Scouting');

    // The scout table must NOT be visible; instead a warning is shown.
    await expect(page.getByTestId('scouts-table')).toHaveCount(0);
    await expect(page.getByTestId('scouting-not-possible')).toBeVisible();
    await expect(page.getByTestId('scouting-not-possible')).toContainText(
      'The next scouting is possible at the earliest on',
    );
  });
});
