import { expect, test } from '@playwright/test';

/**
 * E2E: every national-team-management page requires a login.
 *
 * A guest visiting any of these pages is redirected to the login form.
 */

// Warm up the application on the first request.  On a freshly seeded stack the
// config.inc.php does not yet contain the default value of every module
// setting (e.g. head_code).  The app appends those defaults during the first
// request, but the template rendering for that very request fails with a
// "Missing configuration" fatal error.  A trivial GET to the home page
// triggers the initialisation so that subsequent requests render correctly.
test.beforeAll(async ({ browser }) => {
  const page = await browser.newPage();
  await page.goto('/');
  await page.close();
});

for (const nationalPage of [
  'nationalteam',
  'nominate-national-players',
  'nationalmatches',
]) {
  test(`${nationalPage} page requires a login`, async ({ page }) => {
    await page.goto(`/?page=${nationalPage}`);
    await expect(page.locator('h1')).toHaveText('Log In');
  });
}

test('logged-in user without national team sees error on nationalteam page', async ({ page }) => {
  // user4 manages Team 4 (a club) but no national team.
  await page.goto('/?page=login');
  await page.fill('#loginstr', 'user4');
  await page.fill('#loginpassword', 'user4');
  await page.click('button[type=submit]');

  await page.goto('/?page=nationalteam');
  // The model throws an exception with the "requires team" message.
  await expect(page.locator('.alert-danger')).toContainText(
    'You need to be the manager of a national team in order to use this feature.',
  );
});
