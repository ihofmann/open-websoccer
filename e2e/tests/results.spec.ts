import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: Frontend "Results and Schedules" page.
 *
 * URL: /?page=results
 *
 * The page has three Bootstrap tabs:
 *   1. Leagues – accordion of countries → season picker → matchday results
 *   2. Cups     – accordion of cups → cup round results
 *   3. Latest Results – most recent completed matches
 *
 * The season-picker and results-list blocks accept query-string parameters
 * (leagueid, seasonid, matchday), so we can load a pre-filtered state via
 * direct URL navigation instead of simulating every AJAX click.
 *
 * Seed data:
 *   League 1, Season 1 ("2025/2026"), matchday 1 – 10 completed matches.
 *   "Demo Cup" with "First Round" – 1 completed + 1 future cup match.
 *   1 completed friendly + 1 scheduled friendly (for latest-results / today).
 *
 * Guests can browse the page, so no login is required.
 */

const RESULTS_URL = '/?page=results';

/** Expand a Bootstrap accordion item and wait for the transition to finish. */
async function expandAccordion(page: Page, buttonSelector: string): Promise<Locator> {
  const button = page.locator(buttonSelector);
  await button.click();
  // The data-bs-target attribute points to the collapse element.
  const targetSel = await button.getAttribute('data-bs-target');
  const panel = page.locator(targetSel!);
  await expect(panel).toBeVisible();
  await expect(panel).not.toHaveClass(/collapsing/);
  return panel;
}

// ---------------------------------------------------------------------------
// Leagues tab
// ---------------------------------------------------------------------------

test.describe('Results page – Leagues tab', () => {

  test('shows the page title and active Leagues tab', async ({ page }) => {
    await page.goto(RESULTS_URL);

    await expect(page.locator('h1')).toHaveText('Results and Schedules');

    const leaguesTab = page.locator('#resultsTab .nav-link', { hasText: 'Leagues' });
    await expect(leaguesTab).toHaveClass(/active/);
  });

  test('lists both countries in collapsed accordions', async ({ page }) => {
    await page.goto(RESULTS_URL);

    const accordion = page.locator('#pagecontent #countries');
    await expect(accordion.locator('.accordion-item')).toHaveCount(2);

    // Countries are ordered alphabetically: Deutschland, England.
    const germanyBtn = accordion.locator('.accordion-button', { hasText: 'Deutschland' });
    const englandBtn = accordion.locator('.accordion-button', { hasText: 'England' });
    await expect(germanyBtn).toBeVisible();
    await expect(englandBtn).toBeVisible();
    // Both start collapsed.
    await expect(germanyBtn).toHaveClass(/collapsed/);
    await expect(englandBtn).toHaveClass(/collapsed/);
  });

  test('expands England and shows Premier Sample League link', async ({ page }) => {
    await page.goto(RESULTS_URL);

    const englandPanel = await expandAccordion(
      page,
      '#pagecontent #countries .accordion-button:has-text("England")'
    );

    const leagueLink = englandPanel.getByRole('link', { name: 'Premier Sample League' });
    await expect(leagueLink).toBeVisible();
  });

  test('shows matchday 1 results when loaded with query parameters', async ({ page }) => {
    // Loading the results page with leagueid, seasonid and matchday pre-fills
    // the season-picker and renders the results-list block server-side.
    await page.goto(`${RESULTS_URL}&leagueid=1&seasonid=1&matchday=1`);

    // The season picker block should be visible with the season selected.
    const seasonSelect = page.locator('#seasonid');
    await expect(seasonSelect).toBeVisible();
    await expect(seasonSelect).toHaveValue('1');

    // The matchday input should show 1.
    const matchdayInput = page.locator('#matchday');
    await expect(matchdayInput).toBeVisible();
    await expect(matchdayInput).toHaveValue('1');

    // The results table should show 10 matches.
    const resultsTable = page.locator('#results-list_block table');
    await expect(resultsTable).toBeVisible();
    await expect(resultsTable.locator('tbody tr')).toHaveCount(10);

    // Spot-check: Team 1 3-0 Team 2.
    await expect(resultsTable).toContainText('Team 1');
    await expect(resultsTable).toContainText('Team 2');
    await expect(resultsTable).toContainText('3 - 0');
  });

  test('result row links to the match details page', async ({ page }) => {
    await page.goto(`${RESULTS_URL}&leagueid=1&seasonid=1&matchday=1`);

    const resultsTable = page.locator('#results-list_block table');
    // Click the result link in the first match row (Team 1 vs Team 2, match id 1).
    await resultsTable.locator('tbody tr').first().getByRole('link', { name: '3 - 0' }).click();

    await expect(page.locator('h1')).toHaveText('Team 1 - Team 2');
  });

  test('shows matchday 2 future matches as unscheduled', async ({ page }) => {
    // Matchday 2 matches are ~10 years in the future, not yet simulated.
    await page.goto(`${RESULTS_URL}&leagueid=1&seasonid=1&matchday=2`);

    const resultsTable = page.locator('#results-list_block table');
    await expect(resultsTable).toBeVisible();
    await expect(resultsTable.locator('tbody tr')).toHaveCount(10);

    // Unscheduled matches show "Match Details" text instead of a score.
    await expect(resultsTable).toContainText('Match Details');
  });
});

// ---------------------------------------------------------------------------
// Cups tab
// ---------------------------------------------------------------------------

test.describe('Results page – Cups tab', () => {

  test('lists the Demo Cup with its round', async ({ page }) => {
    await page.goto(RESULTS_URL);

    // Click the Cups tab.
    const cupsTab = page.locator('#resultsTab .nav-link', { hasText: 'Cups' });
    await cupsTab.click();

    // The cups-list block loads via AJAX into #cups-content.
    const cupsAccordion = page.locator('#cups-content #cups');
    await expect(cupsAccordion).toBeVisible({ timeout: 15_000 });
    await expect(cupsAccordion.locator('.accordion-item')).toHaveCount(1);
    await expect(cupsAccordion.locator('.accordion-button')).toContainText('Demo Cup');
  });

  test('expands Demo Cup and shows the First Round link', async ({ page }) => {
    await page.goto(RESULTS_URL);

    // Activate the Cups tab and wait for the AJAX content.
    await page.locator('#resultsTab .nav-link', { hasText: 'Cups' }).click();
    const cupsAccordion = page.locator('#cups-content #cups');
    await expect(cupsAccordion).toBeVisible({ timeout: 15_000 });

    // Expand the Demo Cup accordion.
    const cupButton = cupsAccordion.locator('.accordion-button');
    await cupButton.click();
    const cupPanelSel = await cupButton.getAttribute('data-bs-target');
    const cupPanel = page.locator(cupPanelSel!);
    await expect(cupPanel).toBeVisible();
    await expect(cupPanel).not.toHaveClass(/collapsing/);

    // The "First Round" link should be present.
    const roundLink = cupPanel.getByRole('link', { name: 'First Round' });
    await expect(roundLink).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// Latest Results tab
// ---------------------------------------------------------------------------

test.describe('Results page – Latest Results tab', () => {

  test('shows recently completed matches', async ({ page }) => {
    await page.goto(RESULTS_URL);

    // Click the Latest Results tab.
    const latestTab = page.locator('#resultsTab .nav-link', { hasText: 'Latest Results' });
    await latestTab.click();

    // The latest-results block loads via AJAX.
    const latestBlock = page.locator('#latest-results_block table');
    await expect(latestBlock).toBeVisible({ timeout: 15_000 });
    // At least the matchday 1 matches and the completed friendly should appear.
    await expect(latestBlock.locator('tbody tr').count()).resolves.toBeGreaterThan(0);

    // Spot-check a known completed match (Team 1 vs Team 2, 3-0).
    await expect(latestBlock).toContainText('Team 1');
    await expect(latestBlock).toContainText('3 - 0');
  });
});
