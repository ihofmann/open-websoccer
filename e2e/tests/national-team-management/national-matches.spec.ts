import { expect, test } from '@playwright/test';
import { loginAs, loginAsUser1, MATCH_IDS } from './_helpers';

/**
 * E2E: the "National Team Matches" page – the formation link, the next-matches
 * AJAX block, and the results AJAX block.
 *
 * Seed data:
 *   Team 41 "England" (user1) has two matches vs Team 42 "Deutschland":
 *     #25  completed 3-1  (yesterday)   → shown in the Results block.
 *     #26  scheduled      (~10 years)   → shown in the Next Matches block.
 *
 * This spec is read-only (no mutations).
 */

test.describe('National Team Matches page (logged in as user1)', () => {
  test('renders the page title', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    await expect(page.locator('h1')).toHaveText('National Team Matches');
  });

  test('shows section headings for next matches and results', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    await expect(page.locator('#pagecontent')).toContainText('Next Matches');
    await expect(page.locator('#pagecontent')).toContainText('Results');
  });

  test('shows the formation link', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    const link = page.getByTestId('nt-formation-link');
    await expect(link).toBeVisible();
    await expect(link).toContainText('Formation');

    // The link points to the formation page with nationalteam=1.
    const href = await link.getAttribute('href');
    expect(href).toContain('page=formation');
    expect(href).toContain('nationalteam=1');
  });

  test('next matches block loads and shows the scheduled match', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    // The AJAX block container is present.
    const nextMatchesBlock = page.getByTestId('nt-next-matches');
    await expect(nextMatchesBlock).toBeVisible();

    // Wait for the AJAX-loaded results table to appear.
    const table = nextMatchesBlock.getByTestId('results-list-table');
    await expect(table).toBeVisible();

    // The table shows England vs Deutschland (the future match #26).
    await expect(table).toContainText('England');
    await expect(table).toContainText('Deutschland');

    // The future match is not simulated, so the result column shows "Match Details".
    const row = nextMatchesBlock.getByTestId('results-list-row').first();
    await expect(row).toContainText('Match Details');
  });

  test('results block loads and shows the completed match with score', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    // The AJAX block container is present.
    const resultsBlock = page.getByTestId('nt-results');
    await expect(resultsBlock).toBeVisible();

    // Wait for the AJAX-loaded results table to appear.
    const table = resultsBlock.getByTestId('results-list-table');
    await expect(table).toBeVisible();

    // The table shows England vs Deutschland (the completed match #25).
    await expect(table).toContainText('England');
    await expect(table).toContainText('Deutschland');

    // The completed match shows the score 3 - 1.
    const row = resultsBlock.getByTestId('results-list-row').first();
    await expect(row).toContainText('3 - 1');
  });

  test('next matches row links to match details page', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    const nextMatchesBlock = page.getByTestId('nt-next-matches');
    const table = nextMatchesBlock.getByTestId('results-list-table');
    await expect(table).toBeVisible();

    // The result column contains a link to the match details page.
    const matchLink = table.locator('a').filter({ hasText: 'Match Details' });
    await expect(matchLink).toBeVisible();
    const href = await matchLink.getAttribute('href');
    expect(href).toContain(`id=${MATCH_IDS.scheduled}`);
  });

  test('results row links to completed match details page', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalmatches');

    const resultsBlock = page.getByTestId('nt-results');
    const table = resultsBlock.getByTestId('results-list-table');
    await expect(table).toBeVisible();

    // The score "3 - 1" is a link to the match details page.
    const matchLink = table.locator('a').filter({ hasText: '3 - 1' });
    await expect(matchLink).toBeVisible();
    const href = await matchLink.getAttribute('href');
    expect(href).toContain(`id=${MATCH_IDS.completed}`);
  });

  test('logged-in user without national team sees error', async ({ page }) => {
    await loginAs(page, 'user4', 'user4');
    await page.goto('/?page=nationalmatches');

    await expect(page.locator('.alert-danger')).toContainText(
      'You need to be the manager of a national team in order to use this feature.',
    );
  });

  test('empty national team matches page shows no-matches messages', async ({ page }) => {
    // user3 manages Team 43 "Italy" which has no matches.
    await loginAs(page, 'user3', 'user3');
    await page.goto('/?page=nationalmatches');

    await expect(page.locator('h1')).toHaveText('National Team Matches');

    // Both blocks show "No matches found".
    await expect(page.getByTestId('nt-next-matches').getByTestId('results-list-no-matches')).toBeVisible();
    await expect(page.getByTestId('nt-results').getByTestId('results-list-no-matches')).toBeVisible();
  });
});
