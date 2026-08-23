import { expect, test } from '@playwright/test';

/**
 * E2E: every youth-team-management page requires a login.
 *
 * A guest visiting any of these pages is redirected to the login form.
 */

for (const youthPage of [
  'youth-team',
  'youth-scouting',
  'youth-matchrequests',
  'youth-matchrequests-create',
  'youth-matches',
  'youth-marketplace',
  'youth-formation',
  'youthplayer-sell',
  'youthplayer-fire',
  'youthplayer-buy',
  'youthplayer-makeprofessional',
]) {
  test(`${youthPage} page requires a login`, async ({ page }) => {
    // youth-formation and youthplayer-* need an id / matchid param to get
    // past the model's renderView.  Using a dummy value is sufficient for the
    // auth check because the redirect happens before the model is invoked.
    const params =
      youthPage === 'youth-formation' ? '&matchid=1' :
      ['youthplayer-sell', 'youthplayer-fire', 'youthplayer-buy', 'youthplayer-makeprofessional'].includes(youthPage)
        ? '&id=1' : '';
    await page.goto(`/?page=${youthPage}${params}`);
    await expect(page.locator('h1')).toHaveText('Log In');
  });
}

test('youth-match page is accessible to guests', async ({ page }) => {
  // The youth-match (report) page has role "user,guest" so it does NOT
  // require a login.
  await page.goto('/?page=youth-match&id=1');
  await expect(page.locator('h1')).not.toHaveText('Log In');
});
