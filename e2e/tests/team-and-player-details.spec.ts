import { expect, test, type Page } from '@playwright/test';

/**
 * E2E: Navigate from the league table to a team detail page and from there
 * to a player detail page, exercising all tabs and the advanced statistics
 * modal.
 *
 * URL: /?page=leagues&id=1 (Premier Sample League)
 *
 * Seed data:
 *   - League 1 contains 20 teams (Team 1 ... Team 20).
 *   - Team 1 is managed by user1 and plays in Sample Arena.
 *   - Team 1 vs Team 2 (3-0) is a completed match on matchday 1.
 *   - Team 1 players are generated, the first one is Player1_T1 Lastname11 (ID 1).
 *   - No match calculation rows exist, so the advanced player statistics modal
 *     shows the "not played yet" message.
 *
 * Guests can browse the pages, so no login is required.
 */

const LEAGUE_URL = '/?page=leagues&id=1';

async function clickTeamTab(page: Page, name: string) {
  await page.locator(`[data-testid="team-tab-${name}"]`).click();
}

async function clickPlayerTab(page: Page, name: string) {
  await page.locator(`[data-testid="player-tab-${name}"]`).click();
}

test.describe('Team and player details navigation', () => {

  test('opens a team from the league table and walks through all tabs', async ({ page }) => {
    await page.goto(LEAGUE_URL);

    await expect(page.locator('h1')).toHaveText('Table');

    // Click Team 1 in the league table.
    const teamLink = page.locator('[data-testid="league-table-team-link-1"]');
    await expect(teamLink).toHaveText('Team 1');
    await teamLink.click();

    await expect(page).toHaveURL(/page=team/);
    await expect(page.locator('h1')).toHaveText('Team 1');

    // General / Summary tab is active by default.
    const generalTab = page.locator('[data-testid="team-tab-content-general"]');
    await expect(generalTab).toBeVisible();
    await expect(generalTab).toContainText('Premier Sample League');
    await expect(generalTab).toContainText('user1');
    await expect(generalTab).toContainText('Sample Arena');
    await expect(generalTab).toContainText('T1');

    // Statistics tab.
    await clickTeamTab(page, 'statistic');
    const statisticTab = page.locator('[data-testid="team-tab-content-statistic"]');
    await expect(statisticTab).toBeVisible();
    await expect(statisticTab).toContainText('This Season');
    await expect(statisticTab).toContainText('Total');
    // Team 1 season stats after matchday 1: 1 match, 1 win, 3 goals, 0 against, 3 points.
    await expect(statisticTab).toContainText('3');
    await expect(statisticTab).toContainText('1');
    await expect(statisticTab).toContainText('0');

    // Results tab (AJAX-loaded).
    await clickTeamTab(page, 'results');
    const resultsContainer = page.locator('[data-testid="team-results-list"]');
    await expect(resultsContainer).toBeVisible();
    await expect(page.locator('[data-testid="results-list-table"] tbody tr')).toHaveCount(2);

    // Players tab (AJAX-loaded).
    await clickTeamTab(page, 'players');
    const playersContainer = page.locator('[data-testid="team-players-list"]');
    await expect(playersContainer).toBeVisible();
    await expect(page.locator('[data-testid="team-player-link-1"]')).toBeVisible();

    // Victories / history tab (pre-rendered, content may be replaced via AJAX).
    await clickTeamTab(page, 'victories');
    const victoriesTab = page.locator('[data-testid="team-tab-content-victories"]');
    await expect(victoriesTab).toBeVisible();
    await expect(victoriesTab).toContainText('league victories');
  });

  test('opens a player from the team players tab and verifies tabs and advanced statistics', async ({ page }) => {
    // Go to Team 1 and open the players tab.
    await page.goto(LEAGUE_URL);
    await page.locator('[data-testid="league-table-team-link-1"]').click();
    await expect(page.locator('h1')).toHaveText('Team 1');

    await clickTeamTab(page, 'players');
    await expect(page.locator('[data-testid="team-players-list"]')).toBeVisible();

    // Click the goalkeeper Player1_T1 (ID 1) in the list.
    const playerLink = page.locator('[data-testid="team-player-link-1"]');
    await expect(playerLink).toContainText('Player1_T1');
    await playerLink.click();

    await expect(page).toHaveURL(/page=player&id=1/);
    await expect(page.locator('h1')).toContainText('Player1_T1');

    // Profile / General tab is active by default.
    const generalTab = page.locator('[data-testid="player-tab-content-general"]');
    await expect(generalTab).toBeVisible();
    await expect(generalTab).toContainText('Team 1');
    await expect(generalTab).toContainText('Goalkeeper');
    await expect(generalTab).toContainText('15.06.1995');
    await expect(generalTab).toContainText('50 000');
    await expect(generalTab).toContainText('725 000');
    await expect(generalTab).toContainText('EUR');

    // Statistics tab.
    await clickPlayerTab(page, 'statistic');
    const statisticTab = page.locator('[data-testid="player-tab-content-statistic"]');
    await expect(statisticTab).toBeVisible();
    await expect(statisticTab).toContainText('This Season');
    await expect(statisticTab).toContainText('Total');
    await expect(statisticTab).toContainText('Show More Statistics');

    // History tab (shows a completed transfer for this player in the seed data).
    await clickPlayerTab(page, 'history');
    const historyTab = page.locator('[data-testid="player-tab-content-history"]');
    await expect(historyTab).toBeVisible();
    await expect(historyTab).toContainText('Transfers');
    await expect(historyTab).toContainText('Team 2');
    await expect(historyTab).toContainText('Team 1');
    await expect(historyTab).toContainText('1 000 000');

    // Go back to statistics and open the advanced statistics modal.
    await clickPlayerTab(page, 'statistic');
    await page.locator('[data-testid="player-advanced-statistics-button"]').click();

    const modalContent = page.locator('[data-testid="player-statistics-per-competition"]');
    await expect(modalContent).toBeVisible();
    await expect(page.locator('[data-testid="player-advanced-statistics-content"]')).toBeVisible();
    await expect(modalContent).toContainText('The player has not played in any matches yet.');

    // Close the modal.
    await page.locator('#statModal .btn-close').click();
    await expect(page.locator('#statModal')).not.toBeVisible();
  });
});
