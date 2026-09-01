import { expect, test } from '@playwright/test';

/**
 * E2E: every team-management page requires a login.
 *
 * A guest visiting any of these pages is redirected to the login form.
 */

for (const teamPage of [
  'office',
  'myteam',
  'formation',
  'training',
  'tickets',
  'finances',
  'stadium',
  'sponsor',
]) {
  test(`${teamPage} page requires a login`, async ({ page }) => {
    await page.goto(`/?page=${teamPage}`);
    await expect(page.getByTestId('page-title')).toHaveText('Log In');
  });
}
