import { expect, test } from '@playwright/test';
import { loginAsUser1, MATCH_IDS } from './_helpers';

/**
 * E2E: the "Youth Match" report page – viewing the result, report messages,
 * player grades and statistics of a completed youth match.
 *
 * Seed data: Match #1 (completed, Team 1 vs Team 2, 2-1) with 7 players and
 * 5 report items (2 goals by "Young Wing", 1 goal by "Guest Midfielder",
 * 1 yellow card, 1 substitution).
 *
 * The match report players reference youth player IDs 6, 7, 9, 12 (Team 1)
 * and 13, 15, 16 (Team 2), none of which are touched by action specs.
 */

test.describe('Youth match report page (match #1)', () => {
  test('shows the match title and final score', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-match&id=${MATCH_IDS.completed}`);

    await expect(page.locator('h1')).toHaveText('Team 1 - Team 2');

    const score = page.getByTestId('youth-match-score');
    await expect(score).toBeVisible();
    await expect(score).toContainText('2');
    await expect(score).toContainText('1');
  });

  test('displays the match report with goals and events', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-match&id=${MATCH_IDS.completed}`);

    const report = page.getByTestId('youth-match-report');
    await expect(report).toBeVisible();

    // The report contains 5 items (3 goals, 1 card, 1 substitution).
    const items = report.locator('li');
    await expect(items).toHaveCount(5);

    // "Young Wing" scored 2 goals (minutes 12 and 85).
    await expect(report).toContainText('Young Wing');
    // "Guest Midfielder" scored 1 goal (minute 55) and got a yellow card.
    await expect(report).toContainText('Guest Midfielder');
    // Substitution: Youth Midfielder replaces Young Forward.
    await expect(report).toContainText('Youth Midfielder');
    await expect(report).toContainText('Young Forward');
  });

  test('lists players of both teams with grades', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-match&id=${MATCH_IDS.completed}`);

    const content = page.locator('#pagecontent');

    // Team 1 players.
    await expect(content).toContainText('Young Defender');
    await expect(content).toContainText('Youth Midfielder');
    await expect(content).toContainText('Young Wing');
    await expect(content).toContainText('Young Forward');

    // Team 2 players.
    await expect(content).toContainText('Guest Keeper');
    await expect(content).toContainText('Guest Midfielder');
    await expect(content).toContainText('Guest Striker');
  });

  test('renders match statistics for both teams', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-match&id=${MATCH_IDS.completed}`);

    const content = page.locator('#pagecontent');

    // The statistics section includes "Shots" and "Average Strength" labels.
    await expect(content).toContainText('Shots');
    await expect(content).toContainText('Average Strength');
  });
});
